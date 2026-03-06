<?php

namespace App\Service;

use App\Repository\ApartmentRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VapiKnowledgeBaseService
{
    private string $apiKey;
    private string $fileIdPath;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ApartmentRepository $apartmentRepository,
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
        $apartments = $this->apartmentRepository->findAll();

        $lines = ["=== Base de Conocimiento: Apartamentos ===", ""];
        $lines[] = "Fecha de actualización: " . (new \DateTimeImmutable())->format('d/m/Y H:i');
        $lines[] = "Total de apartamentos: " . count($apartments);
        $lines[] = "";
        $lines[] = "---";
        $lines[] = "";

        foreach ($apartments as $apartment) {
            $available = $apartment->isAvailable() ? 'Sí' : 'No';
            $lines[] = "Apartamento: " . $apartment->getName();
            $lines[] = "  Dirección: " . $apartment->getAddress();
            $lines[] = "  Precio: " . number_format($apartment->getPrice(), 0, ',', '.') . " €/mes";
            $lines[] = "  Disponible: " . $available;
            $lines[] = "";
        }

        // Summary of available apartments
        $availableApartments = array_filter(
            $apartments,
            fn($a) => $a->isAvailable()
        );

        $lines[] = "---";
        $lines[] = "";
        $lines[] = "Resumen: Hay " . count($availableApartments) . " apartamentos disponibles de un total de " . count($apartments) . ".";
        $lines[] = "";
        $lines[] = "Apartamentos disponibles:";
        foreach ($availableApartments as $apt) {
            $lines[] = "  - " . $apt->getName() . " (" . $apt->getAddress() . ") por " . number_format($apt->getPrice(), 0, ',', '.') . " €/mes";
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
