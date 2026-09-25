<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PatentController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'query' => [
                'required',
                'string',
                'max:500',
            ],
        ]);

        $search = trim($request->input('query'));

        if (! $search) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a patent name, ID, inventor, or keyword.',
            ], 422);
        }

        $lensSearch = $search;

        if (preg_match('/^[A-Za-z]{2}(\d+)(?:[A-Za-z]\d)?$/', $search, $matches)) {
            $lensSearch .= ' OR (application_number:'.$matches[1].')';
        }

        if (preg_match('/^A[12](\d{4})(\d{6})\b/i', $search, $matches)) {
            $lensSearch .= ' OR WO'.$matches[1].$matches[2];
        }

        /*
        |--------------------------------------------------------------------------
        | Lens API Search
        |--------------------------------------------------------------------------
        |
        | If the user enters a Lens query such as:
        |
        | inventor:"John Smith"
        |
        | Lens will process it as a field-specific query.
        |
        | For normal searches, we search across several useful fields.
        |
        */

        $query = [
            'query' => [
                'query_string' => [
                    'query' => $lensSearch,
                    'fields' => [
                        'title',
                        'abstract',
                        'claims',
                        'description',
                        'ids',
                        'lens_id',
                        'biblio.application_number',
                        'inventor.name',
                        'applicant.name',
                        'owner_all.name',
                        'cpc.symbol',
                        'ipc.symbol',
                    ],
                    'default_operator' => 'or',
                ],
            ],

            'size' => 10,

            'sort' => [
                [
                    'date_published' => 'desc',
                ],
            ],

            'include' => [
                'lens_id',
                'biblio',
                'abstract',
                'legal_status',
                'publication_type',
                'jurisdiction',
                'doc_number',
                'date_published',
            ],
        ];

        try {

            $response = Http::withToken(
                config('services.lens.token')
            )
                ->withOptions([
                    'verify' => config('services.lens.ca_bundle') ?: true,
                ])
                ->acceptJson()
                ->timeout(30)
                ->post(
                    config('services.lens.url'),
                    $query
                );

            if ($response->failed()) {

                return response()->json([
                    'success' => false,
                    'message' => 'The patent search service is currently unavailable.',
                    'status' => $response->status(),
                    'details' => config('app.debug')
                        ? $response->json()
                        : null,
                ], 502);
            }

            $data = $response->json();

            return response()->json([
                'success' => true,
                'total' => $data['total'] ?? 0,
                'data' => $data['data'] ?? [],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to the Lens patent service.',
                'details' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }
}
