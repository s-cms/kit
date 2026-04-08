<?php

namespace SmartCms\Kit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use SmartCms\Kit\Models\Media;
use SmartCms\Kit\Services\MediaLibraryService;

class MediaPickerController extends Controller
{
    public function __construct(
        protected MediaLibraryService $mediaService
    ) {}

    /**
     * Handle file upload
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:10240', // 10MB max
            'collection' => 'string',
        ]);

        try {
            $image = $this->mediaService->storeUploadedFile(
                $request->file('file'),
                $request->input('collection', config('kit.media.collection_name'))
            );

            // Refresh from DB to get final URL after observer (WebP conversion, responsive images)
            if (isset($image['media_id'])) {
                $media = Media::find($image['media_id']);
                if ($media) {
                    $image['source'] = $media->getUrl();
                    $image['width'] = $media->width;
                    $image['height'] = $media->height;
                }
            }

            return response()->json([
                'success' => true,
                'image' => $image,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Fetch image from URL
     */
    public function fetchUrl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
            'collection' => 'string',
        ]);

        try {
            $image = $this->mediaService->storeFromUrl(
                $request->input('url'),
                $request->input('collection', config('kit.media.collection_name'))
            );

            if (isset($image['media_id'])) {
                $media = Media::find($image['media_id']);
                if ($media) {
                    $image['source'] = $media->getUrl();
                    $image['width'] = $media->width;
                    $image['height'] = $media->height;
                }
            }

            return response()->json([
                'success' => true,
                'image' => $image,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Search Unsplash
     */
    public function unsplashSearch(Request $request): JsonResponse
    {
        $query = $request->input('query');

        if (! config('kit.unsplash.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Unsplash is not enabled',
            ], 403);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Client-ID ' . config('kit.unsplash.access_key'),
            ])->get('https://api.unsplash.com/search/photos', [
                'query' => $query,
                'per_page' => 12,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'results' => $response->json('results', []),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Unsplash API request failed',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download image from Unsplash
     */
    public function unsplashDownload(Request $request): JsonResponse
    {
        if (! config('kit.unsplash.enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Unsplash is not enabled',
            ], 403);
        }

        try {
            $photo = $request->input('photo');
            $collection = $request->input('collection', config('kit.media.collection_name'));

            // Trigger download endpoint (required by Unsplash API)
            if (isset($photo['links']['download_location'])) {
                Http::withHeaders([
                    'Authorization' => 'Client-ID ' . config('kit.unsplash.access_key'),
                ])->get($photo['links']['download_location']);
            }

            // Download the image
            $image = $this->mediaService->storeFromUrl(
                $photo['urls']['regular'],
                $collection,
                [
                    'alt' => [app()->getLocale() => $photo['alt_description'] ?? $photo['description'] ?? ''],
                    'source' => 'unsplash',
                    'source_id' => $photo['id'],
                    'source_author' => $photo['user']['name'] ?? null,
                    'source_url' => $photo['links']['html'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'image' => $image,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Browse media library
     */
    public function library(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $query = Media::query()->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $media = $query->limit(24)->get();

        return response()->json([
            'success' => true,
            'media' => $media->map(function (Media $item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'url' => $item->getUrl(),
                    'alt_translations' => $item->alt ?? [],
                    'mime_type' => $item->mime_type,
                ];
            }),
        ]);
    }

    /**
     * Update media name and alt text
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'name' => 'nullable|string|max:255',
            'alt' => 'nullable|array',
            'alt.*' => 'nullable|string|max:255',
        ]);

        $media = Media::find($request->input('id'));
        if (! $media) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        if ($request->has('name')) {
            $media->name = $request->input('name');
        }

        if ($request->has('alt')) {
            $media->alt = $request->input('alt');
        }

        $media->save();

        return response()->json(['success' => true]);
    }
}
