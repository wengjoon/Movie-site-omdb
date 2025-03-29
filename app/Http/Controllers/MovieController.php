<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TmdbService;

class MovieController extends Controller
{
    /**
     * The OMDB API service
     */
    protected $movieService;
    
    /**
     * Constructor to initialize API service
     */
    public function __construct(TmdbService $movieService)
    {
        $this->movieService = $movieService;
    }

    /**
     * Display the search homepage with top rated movies
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Clear cache to ensure we get fresh OMDB data
        $cacheKey = 'top_rated_movies';
        Cache::forget($cacheKey);
        
        // Log that we're refreshing the cache
        Log::info('MovieController: Refreshing top rated movies cache to pull from OMDB');
        
        // Cache top rated movies for a shorter time during the API transition
        $topRatedMovies = Cache::remember($cacheKey, 1440, function () {
            $response = $this->movieService->getTopRatedMovies();
            
            // Log the response for debugging
            Log::info('MovieController: Got ' . count($response['results'] ?? []) . ' top rated movies from OMDB', [
                'first_movie' => $response['results'][0]['title'] ?? 'None',
                'has_poster' => isset($response['results'][0]['poster_path']) ? 'Yes' : 'No'
            ]);
            
            return $response['results'] ?? [];
        });

        // Split movies into rows (4 movies per row)
        $movieRows = array_chunk($topRatedMovies, 4);
        
        return view('movies.index', [
            'movieRows' => $movieRows
        ]);
    }

    /**
     * Search for movies using the OMDB API
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $page = $request->input('page', 1);

        if (empty($query)) {
            return redirect()->route('movies.index');
        }

        // Use a cache key for the search
        $cacheKey = 'movie_search_' . md5($query . '_' . $page);
        
        $results = Cache::remember($cacheKey, 3600, function () use ($query, $page) {
            $searchResults = $this->movieService->searchMovies($query, $page);
            
            return [
                'results' => $searchResults['results'] ?? [],
                'total_pages' => $searchResults['total_pages'] ?? 1,
                'current_page' => $searchResults['page'] ?? $page,
                'query' => $query,
            ];
        });

        return view('movies.search', $results);
    }

    /**
     * Display the detailed information for a specific movie
     *
     * @param  string  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $cacheKey = 'movie_details_' . $id;
        
        try {
            $movie = Cache::remember($cacheKey, 3600, function () use ($id) {
                // Get movie details from OMDB
                $details = $this->movieService->getMovieDetails($id);
                
                // Immediately check if we got a valid response with a title
                if (!isset($details['title']) || $details['title'] === 'Movie not found') {
                    throw new \Exception('Movie not found');
                }
                
                return $details;
            });
            
            return view('movies.show', ['movie' => $movie]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Movie not found: ' . $id . ' - ' . $e->getMessage());
            
            // Force a 404 response
            abort(404);
        }
    }
}