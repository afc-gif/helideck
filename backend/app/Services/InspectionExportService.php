<?php

namespace App\Services;

use App\Models\Inspection;

/**
 * InspectionExportService
 * 
 * Handles PDF and CSV export for inspections
 */
class InspectionExportService
{
    /**
     * Export inspection as PDF
     * 
     * Returns HTML string that can be passed to DomPDF
     */
    public function exportPdf(Inspection $inspection): string
    {
        $formData = $inspection->form_data;

        // Build PDF HTML structure
        $html = view('exports.inspection-pdf', [
            'inspection' => $inspection,
            'formData' => $formData,
        ])->render();

        return $html;
    }

    /**
     * Get CSV headers
     */
    public function getCsvHeaders(): array
    {
        return [
            'UUID',
            'Status',
            'Landing Site',
            'Inspector',
            'Created Date',
            'Updated Date',
            'Synced Date',
        ];
    }

    /**
     * Get single inspection as CSV row
     */
    public function getInspectionCsvRow(Inspection $inspection): array
    {
        return [
            $inspection->uuid,
            ucfirst($inspection->status),
            $inspection->getLandingSiteName(),
            $inspection->inspector->name,
            $inspection->created_at->format('Y-m-d H:i:s'),
            $inspection->updated_at->format('Y-m-d H:i:s'),
            $inspection->synced_at ? $inspection->synced_at->format('Y-m-d H:i:s') : 'Never',
        ];
    }

    /**
     * Format inspection data for display
     */
    public function formatInspectionData(array $formData): array
    {
        // Flatten nested JSON for better CSV display
        $flattened = [];

        foreach ($formData as $section => $data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $flattened["{$section}.{$key}"] = json_encode($value);
                    } else {
                        $flattened["{$section}.{$key}"] = $value;
                    }
                }
            } else {
                $flattened[$section] = $data;
            }
        }

        return $flattened;
    }
}
