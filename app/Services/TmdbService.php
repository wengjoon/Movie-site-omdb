<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    /**
     * Base OMDB API URL
     */
    protected $apiUrl;
    
    /**
     * API Key configuration
     */
    protected $apiKey;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = env('OMDB_API_URL', 'https://www.omdbapi.com/');
        $this->apiKey = env('OMDB_API_KEY', '918f232b');
    }
    
    /**
     * Make a request to OMDB API
     */
    public function makeRequest($params = [])
    {
        // Add API key to params
        $params['apikey'] = $this->apiKey;
        
        // Create a cache key from the request
        $cacheKey = 'omdb_' . md5(json_encode($params));
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // Throttle requests with a 250ms delay
            $this->throttleRequests();
            
            $response = Http::get($this->apiUrl, $params);
            
            // If successful, cache and return
            if ($response->successful() && $response->json('Response') === 'True') {
                $data = $response->json();
                Cache::put($cacheKey, $data, 604800); // 1 week
                return $data;
            } else {
                // Log the error
                Log::error('OMDB API Error', [
                    'params' => $params,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                
                // Return empty result
                return ['Response' => 'False'];
            }
        } catch (\Exception $e) {
            // Log the exception
            Log::error('OMDB API Exception: ' . $e->getMessage());
            
            // Return empty result
            return ['Response' => 'False'];
        }
    }
    
    /**
     * Throttle requests to prevent overloading the API
     */
    protected function throttleRequests()
    {
        // Get the timestamp of the last API call
        $lastCallTimestamp = Cache::get('omdb_last_api_call', 0);
        $now = microtime(true);
        
        // If the last call was made less than 250ms ago, wait
        if ($now - $lastCallTimestamp < 0.25) {
            $sleepTime = 0.25 - ($now - $lastCallTimestamp);
            usleep($sleepTime * 1000000); // Convert to microseconds
        }
        
        // Update the last call timestamp
        Cache::put('omdb_last_api_call', microtime(true), 60);
    }
    
    /**
     * Search for movies
     */
    public function searchMovies($query, $page = 1)
    {
        $params = [
            's' => $query,
            'type' => 'movie',
            'page' => $page
        ];
        
        $response = $this->makeRequest($params);
        
        // Format response to match the application's expected structure
        $formattedResponse = [
            'results' => [],
            'page' => $page,
            'total_pages' => 1,
            'total_results' => 0
        ];
        
        if (isset($response['Search']) && is_array($response['Search'])) {
            // Transform OMDB results to match TMDB format
            $results = [];
            foreach ($response['Search'] as $movie) {
                // Get IMDB rating if available (requires an additional API call)
                $detailParams = [
                    'i' => $movie['imdbID'],
                    'plot' => 'short'
                ];
                
                $detailResponse = $this->makeRequest($detailParams);
                $rating = 0;
                $votes = 0;
                $plot = '';
                
                if($detailResponse['Response'] === 'True') {
                    $rating = (float)($detailResponse['imdbRating'] ?? 0);
                    $votes = isset($detailResponse['imdbVotes']) ? (int)str_replace(',', '', $detailResponse['imdbVotes']) : 0;
                    $plot = $detailResponse['Plot'] !== 'N/A' ? $detailResponse['Plot'] : '';
                }
                
                $results[] = [
                    'id' => $movie['imdbID'],
                    'title' => $movie['Title'],
                    'release_date' => $movie['Year'] . '-01-01', // OMDB only gives year
                    'poster_path' => $movie['Poster'] !== 'N/A' ? $movie['Poster'] : null,
                    'vote_average' => $rating,
                    'vote_count' => $votes,
                    'overview' => $plot,
                ];
            }
            
            $formattedResponse['results'] = $results;
            $formattedResponse['total_results'] = (int)($response['totalResults'] ?? count($results));
            
            // Calculate total pages (10 results per page)
            $formattedResponse['total_pages'] = ceil($formattedResponse['total_results'] / 10);
        }
        
        return $formattedResponse;
    }
    
    /**
     * Get movie details
     */
    public function getMovieDetails($movieId)
    {
        $params = [
            'i' => $movieId,
            'plot' => 'full'
        ];
        
        $response = $this->makeRequest($params);
        
        if ($response['Response'] === 'True') {
            // Format OMDB response to match TMDB format
            $formattedResponse = [
                'id' => $response['imdbID'],
                'title' => $response['Title'],
                'original_title' => $response['Title'],
                'release_date' => $this->formatReleaseDate($response['Released']),
                'poster_path' => $response['Poster'] !== 'N/A' ? $response['Poster'] : null,
                'backdrop_path' => null, // OMDB doesn't provide backdrop
                'vote_average' => (float)($response['imdbRating'] ?? 0),
                'vote_count' => (int)str_replace(',', '', $response['imdbVotes'] ?? 0),
                'overview' => $response['Plot'] !== 'N/A' ? $response['Plot'] : '',
                'tagline' => '',
                'runtime' => $this->extractRuntime($response['Runtime']),
                'genres' => $this->formatGenres($response['Genre']),
                'directors' => $this->extractDirectors($response['Director']),
                'top_cast' => $this->extractCast($response['Actors']),
                'videos' => [],
                'credits' => [
                    'cast' => $this->formatCast($response['Actors']),
                    'crew' => $this->formatCrew($response['Director'], $response['Writer'])
                ]
            ];
            
            // Add ratings for display
            $formattedResponse['ratings'] = $response['Ratings'] ?? [];
            
            // Add additional OMDB fields for display
            foreach(['Rated', 'Awards', 'Production', 'Country', 'Language', 'BoxOffice', 'Writer'] as $field) {
                if(isset($response[$field]) && $response[$field] !== 'N/A') {
                    $formattedResponse[$field] = $response[$field];
                }
            }
            
            return $formattedResponse;
        }
        
        // Return empty structure if movie not found
        return [
            'id' => $movieId,
            'title' => 'Movie not found',
            'overview' => 'Details not available',
            'poster_path' => null,
            'directors' => [],
            'top_cast' => []
        ];
    }
    
    /**
     * Format release date from OMDB format to YYYY-MM-DD
     */
    protected function formatReleaseDate($released)
    {
        if ($released === 'N/A') {
            return null;
        }
        
        try {
            $date = \DateTime::createFromFormat('d M Y', $released);
            return $date ? $date->format('Y-m-d') : null;
        } catch (\Exception $e) {
            // If parsing fails, try to extract just the year
            if (preg_match('/(\d{4})/', $released, $matches)) {
                return $matches[1] . '-01-01';
            }
            return null;
        }
    }
    
    /**
     * Extract runtime in minutes from OMDB format
     */
    protected function extractRuntime($runtime)
    {
        if ($runtime === 'N/A') {
            return 0;
        }
        
        if (preg_match('/(\d+)\s+min/', $runtime, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
    }
    
    /**
     * Format genres from comma-separated string to array of objects
     */
    protected function formatGenres($genreString)
    {
        if ($genreString === 'N/A') {
            return [];
        }
        
        $genres = explode(', ', $genreString);
        $result = [];
        
        foreach ($genres as $genre) {
            $result[] = [
                'id' => md5($genre), // Generate a pseudo-id
                'name' => $genre
            ];
        }
        
        return $result;
    }
    
    /**
     * Extract directors from comma-separated string
     */
    protected function extractDirectors($directorString)
    {
        if ($directorString === 'N/A') {
            return [];
        }
        
        return explode(', ', $directorString);
    }
    
    /**
     * Extract cast from comma-separated string
     */
    protected function extractCast($castString)
    {
        if ($castString === 'N/A') {
            return [];
        }
        
        return explode(', ', $castString);
    }
    
    /**
     * Format cast for credits section
     */
    protected function formatCast($castString)
    {
        if ($castString === 'N/A') {
            return [];
        }
        
        $castNames = explode(', ', $castString);
        $cast = [];
        
        foreach ($castNames as $index => $name) {
            $cast[] = [
                'id' => $index,
                'name' => $name,
                'character' => '',
                'order' => $index
            ];
        }
        
        return $cast;
    }
    
    /**
     * Format crew for credits section
     */
    protected function formatCrew($directorString, $writerString)
    {
        $crew = [];
        
        // Add directors
        if ($directorString !== 'N/A') {
            $directors = explode(', ', $directorString);
            foreach ($directors as $index => $name) {
                $crew[] = [
                    'id' => 'd' . $index,
                    'name' => $name,
                    'job' => 'Director',
                    'department' => 'Directing'
                ];
            }
        }
        
        // Add writers
        if ($writerString !== 'N/A') {
            $writers = explode(', ', $writerString);
            foreach ($writers as $index => $name) {
                $crew[] = [
                    'id' => 'w' . $index,
                    'name' => $name,
                    'job' => 'Writer',
                    'department' => 'Writing'
                ];
            }
        }
        
        return $crew;
    }
    
    /**
     * Get popular movies (emulated as OMDB doesn't have this endpoint)
     */
    public function getPopularMovies($page = 1)
    {
        // OMDB doesn't have a popular movies endpoint, so we'll search for some popular terms
        $popularQueries = ['action', 'drama', 'comedy', 'thriller', 'romance', 'sci-fi'];
        $query = $popularQueries[array_rand($popularQueries)];
        
        return $this->searchMovies($query, $page);
    }
    
    /**
     * Get top rated movies (emulated as OMDB doesn't have this endpoint)
     */
    public function getTopRatedMovies($page = 1)
{
    // For top rated, we'll use a curated list of known top IMDb movies
    $topRatedMovies = [
        'tt0111161', // The Shawshank Redemption
        'tt0068646', // The Godfather
        'tt0071562', // The Godfather Part II
        'tt0468569', // The Dark Knight
        'tt0050083', // 12 Angry Men
        'tt0108052', // Schindler's List
        'tt0167260', // The Lord of the Rings: The Return of the King
        'tt0110912', // Pulp Fiction
        // Adding more for variety
        'tt0137523', // Fight Club
        'tt0109830', // Forrest Gump
        'tt0080684', // Star Wars: Episode V - The Empire Strikes Back
        'tt0133093', // The Matrix
        'tt0099685', // Goodfellas
        'tt0073486', // One Flew Over the Cuckoo's Nest
        'tt0047478', // Seven Samurai
        'tt0114369', // Se7en
    ];
    
    // Shuffle the array to get different movies each time
    shuffle($topRatedMovies);
    
    $results = [];
    $startIndex = ($page - 1) * 8;
    $endIndex = min($startIndex + 8, count($topRatedMovies));
    
    // Log the process for debugging
    \Log::info('OMDB getTopRatedMovies - Fetching movies', [
        'page' => $page,
        'movies_to_fetch' => array_slice($topRatedMovies, $startIndex, $endIndex - $startIndex)
    ]);
    
    for ($i = $startIndex; $i < $endIndex; $i++) {
        if (isset($topRatedMovies[$i])) {
            $imdbId = $topRatedMovies[$i];
            
            // Try to get the movie details
            try {
                \Log::info('OMDB getTopRatedMovies - Fetching details for ' . $imdbId);
                $details = $this->getMovieDetails($imdbId);
                
                // Only add valid results
                if (isset($details['title']) && $details['title'] !== 'Movie not found') {
                    // Ensure poster is properly set
                    if (!empty($details['Poster']) && $details['Poster'] !== 'N/A') {
                        // If the API returned Poster in the main object
                        $details['poster_path'] = $details['Poster'];
                    } elseif (!empty($details['poster_path']) && !str_starts_with($details['poster_path'], 'http')) {
                        // If we have a path but it's not a full URL (TMDB style), convert it to full URL
                        $details['poster_path'] = 'https://image.tmdb.org/t/p/w500' . $details['poster_path'];
                    }
                    
                    // Make sure we have directors and cast for display
                    if (empty($details['directors']) && !empty($details['Director']) && $details['Director'] !== 'N/A') {
                        $details['directors'] = explode(', ', $details['Director']);
                    }
                    
                    if (empty($details['top_cast']) && !empty($details['Actors']) && $details['Actors'] !== 'N/A') {
                        $details['top_cast'] = explode(', ', $details['Actors']);
                    }
                    
                    $results[] = $details;
                    \Log::info('OMDB getTopRatedMovies - Successfully added: ' . $details['title'] . ' with poster: ' . ($details['poster_path'] ?? 'None'));
                } else {
                    \Log::warning('OMDB getTopRatedMovies - Failed to get details for ' . $imdbId);
                }
            } catch (\Exception $e) {
                \Log::error('OMDB getTopRatedMovies - Error fetching details for ' . $imdbId . ': ' . $e->getMessage());
            }
        }
    }
    
    \Log::info('OMDB getTopRatedMovies - Completed with ' . count($results) . ' results');
    
    return [
        'results' => $results,
        'page' => $page,
        'total_pages' => ceil(count($topRatedMovies) / 8),
        'total_results' => count($topRatedMovies)
    ];
}
}