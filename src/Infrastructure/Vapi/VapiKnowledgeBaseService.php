<?php

namespace App\Infrastructure\Vapi;

use App\Domain\Apartment\ApartmentRepositoryInterface;
use App\Domain\Apartment\VapiKnowledgeBaseServiceInterface;
use Psr\Log\LoggerInterface;
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
        if (empty($this->apiKey)) {
            $this->logger->warning('VAPI_API_KEY is not set — skipping Knowledge Base sync.');
            return;
        }

        // 1. Generate the document content
        $content = $this->generateDocument();

        // 2. Delete the previous file if it exists
        $this->deletePreviousFile();

        // 3. Upload the new file
        $this->uploadFile($content);
    }

    private function generateDocument(): string
    {
        $availableApartments = $this->apartmentRepository->findAvailable();

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

    private function deletePreviousFile(): void
    {
        if (!file_exists($this->fileIdPath)) {
            return;
        }

        $previousFileId = trim(file_get_contents($this->fileIdPath));
        if (empty($previousFileId)) {
            return;
        }

        try {
            $this->httpClient->request('DELETE', 'https://api.vapi.ai/file/' . $previousFileId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);
            $this->logger->info('Vapi: deleted previous file {fileId}', ['fileId' => $previousFileId]);
        } catch (\Throwable $e) {
            $this->logger->warning('Vapi: could not delete previous file: {error}', ['error' => $e->getMessage()]);
        }

        @unlink($this->fileIdPath);
    }

    private function uploadFile(string $content): void
    {
        // Write content to a temp file for multipart upload
        $tmpFile = tempnam(sys_get_temp_dir(), 'vapi_kb_');
        $tmpFilePath = $tmpFile . '.txt';
        rename($tmpFile, $tmpFilePath);
        file_put_contents($tmpFilePath, $content);

        try {
            $response = $this->httpClient->request('POST', 'https://api.vapi.ai/file', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'body' => [
                    'file' => fopen($tmpFilePath, 'r'),
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['id'])) {
                // Ensure the share directory exists
                $dir = dirname($this->fileIdPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                file_put_contents($this->fileIdPath, $data['id']);
                $this->logger->info('Vapi: uploaded KB file with id {fileId}', ['fileId' => $data['id']]);
            } else {
                $this->logger->error('Vapi: upload response did not contain file id', ['response' => $data]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Vapi: failed to upload KB file: {error}', ['error' => $e->getMessage()]);
        } finally {
            @unlink($tmpFilePath);
        }
    }
}
