<?php

namespace App\Infrastructure\Vapi;

use App\Domain\Apartment\ApartmentRepositoryInterface;
use App\Domain\Apartment\VapiKnowledgeBaseServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VapiKnowledgeBaseService implements VapiKnowledgeBaseServiceInterface
{
    private string $apiKey;
    private string $fileIdPath;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApartmentRepositoryInterface $apartmentRepository,
        private readonly LoggerInterface $logger,
        string $vapiApiKey,
        string $shareDir,
    ) {
        $this->apiKey = $vapiApiKey;
        $this->fileIdPath = $shareDir . '/vapi_file_id.txt';
    }

    /**
     * Sync all apartments to Vapi as a Knowledge Base document.
     * Uploads a new file and deletes the previous one.
     */
    public function syncKnowledgeBase(): void
    {
        if (empty($this->apiKey) || str_contains($this->apiKey, 'your_vapi_api_key_here')) {
            $this->logger->warning('VAPI_API_KEY is not configured or is a placeholder — skipping Knowledge Base sync.');
            return;
        }

        // 1. Generate the document content
        $availableApartments = $this->apartmentRepository->findAvailable();
        $content = $this->generateDocument($availableApartments);

        // 2. Start deleting the previous file if it exists (async)
        $deleteTask = $this->startDeletingPreviousFile();

        // 3. Start uploading the new file (async)
        $uploadTask = $this->startUploadingFile($content);

        // 4. Wait for both responses to complete
        if ($deleteTask) {
            $this->finishDeletingPreviousFile($deleteTask['response'], $deleteTask['fileId']);
        }
        $success = false;
        if ($uploadTask) {
            $success = $this->finishUploadingFile($uploadTask['response'], $uploadTask['tmpFilePath']);
        }

        if ($success) {
            $now = new \DateTimeImmutable();
            foreach ($availableApartments as $apt) {
                $apt->setVapiSyncedAt($now);
                $this->apartmentRepository->save($apt);
            }
        } else {
            throw new \RuntimeException('Failed to upload the Knowledge Base file to Vapi. Check the logs for details.');
        }
    }

    /**
     * @param \App\Domain\Apartment\Apartment[] $availableApartments
     */
    private function generateDocument(array $availableApartments): string
    {
        $lines = ["=== Base de Conocimiento: Apartamentos ===", ""];
        $lines[] = "Fecha de actualización: " . (new \DateTimeImmutable())->format('d/m/Y H:i');
        $lines[] = "Total de apartamentos disponibles: " . count($availableApartments);
        $lines[] = "";
        $lines[] = "---";
        $lines[] = "";
        $lines[] = "Apartamentos disponibles:";
        $lines[] = "";

        foreach ($availableApartments as $apt) {
            $lines[] = "Apartamento: " . $apt->getName();
            $lines[] = "  Dirección: " . $apt->getAddress();
            $lines[] = "  Precio: " . number_format($apt->getPrice(), 0, ',', '.') . " €/mes";
            $lines[] = "  Disponible: Sí";
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{response: \Symfony\Contracts\HttpClient\ResponseInterface, fileId: string}|null
     */
    private function startDeletingPreviousFile(): ?array
    {
        if (!file_exists($this->fileIdPath)) {
            return null;
        }

        $previousFileId = trim(file_get_contents($this->fileIdPath));
        if (empty($previousFileId)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('DELETE', 'https://api.vapi.ai/file/' . $previousFileId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);
            return ['response' => $response, 'fileId' => $previousFileId];
        } catch (\Throwable $e) {
            $this->logger->warning('Vapi: could not start deleting previous file: {error}', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function finishDeletingPreviousFile(\Symfony\Contracts\HttpClient\ResponseInterface $response, string $fileId): void
    {
        try {
            // Accessing the status code will wait for the response to resolve
            $response->getStatusCode();
            $this->logger->info('Vapi: deleted previous file {fileId}', ['fileId' => $fileId]);
        } catch (\Throwable $e) {
            $this->logger->warning('Vapi: could not delete previous file: {error}', ['error' => $e->getMessage()]);
        }

        if (file_exists($this->fileIdPath)) {
            unlink($this->fileIdPath);
        }
    }

    /**
     * @return array{response: \Symfony\Contracts\HttpClient\ResponseInterface, tmpFilePath: string}|null
     */
    private function startUploadingFile(string $content): ?array
    {
        // Write content to a temp file for multipart upload
        $tmpFile = tempnam(sys_get_temp_dir(), 'vapi_kb_');
        $tmpFilePath = $tmpFile . '.txt';
        rename($tmpFile, $tmpFilePath);
        chmod($tmpFilePath, 0600);
        file_put_contents($tmpFilePath, $content);

        $formData = new FormDataPart([
            'file' => DataPart::fromPath($tmpFilePath, 'vapi_kb.txt', 'text/plain')
        ]);

        try {
            $headers = $formData->getPreparedHeaders()->toArray();
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;

            $response = $this->httpClient->request('POST', 'https://api.vapi.ai/file', [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]);

            return ['response' => $response, 'tmpFilePath' => $tmpFilePath];
        } catch (\Throwable $e) {
            $this->logger->error('Vapi: failed to start uploading KB file: {error}', ['error' => $e->getMessage()]);
            if (file_exists($tmpFilePath)) {
                unlink($tmpFilePath);
            }
            return null;
        }
    }

    private function finishUploadingFile(\Symfony\Contracts\HttpClient\ResponseInterface $response, string $tmpFilePath): bool
    {
        $success = false;
        try {
            $data = $response->toArray();

            if (isset($data['id'])) {
                // Ensure the share directory exists
                $dir = dirname($this->fileIdPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                file_put_contents($this->fileIdPath, $data['id']);
                $this->logger->info('Vapi: uploaded KB file with id {fileId}', ['fileId' => $data['id']]);
                $success = true;
            } else {
                $this->logger->error('Vapi: upload response did not contain file id', ['response' => $data]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Vapi: failed to upload KB file: {error}', ['error' => $e->getMessage()]);
        } finally {
            if (file_exists($tmpFilePath)) {
                unlink($tmpFilePath);
            }
        }

        return $success;
    }
}
