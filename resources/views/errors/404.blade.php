@extends('layouts.app')

@section('seo_title', 'Page Not Found - 123 Movies Pro')
@section('seo_description', 'The page you were looking for could not be found. Explore our collection of free movies and TV shows.')

@section('content')
<div class="container error-container py-5">
    <div class="row">
        <div class="col-md-10 col-lg-8 mx-auto text-center mb-5">
            <i class="bi bi-exclamation-circle text-danger error-icon"></i>
            <h1 class="display-1 fw-bold text-danger mb-4">404</h1>
            <h2 class="fw-light mb-4">Page Not Found</h2>
            <p class="lead">Oops! The page you're looking for doesn't exist. It might have been moved or deleted.</p>
            <div class="mt-4">
                <a href="{{ route('movies.index') }}" class="btn btn-danger btn-lg px-4 me-2">
                    <i class="bi bi-house-door-fill me-2"></i> Back to Home
                </a>
                <button onclick="history.back()" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-arrow-left me-2"></i> Go Back
                </button>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="text-center mb-4">Recommended Movies</h3>
            <div class="row mt-4 g-4 popular-movies">
                {{-- Loading spinner while fetching movies --}}
                <div class="col-12 text-center loading-spinner">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                
                {{-- Movies will be dynamically loaded here --}}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .error-container {
        min-height: 70vh;
        padding-top: 3rem;
    }
    
    .error-icon {
        font-size: 5rem;
        margin-bottom: 1rem;
    }
    
    .movie-suggestion-card {
        background-color: var(--netflix-light-dark);
        border-radius: 4px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .movie-suggestion-card:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
        z-index: 1;
    }
    
    .movie-suggestion-img {
        height: 280px;
        object-fit: cover;
    }
    
    .movie-suggestion-title {
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .movie-suggestion-rating {
        color: #FFC107;
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch popular movies from OMDB
        fetchPopularMovies();
    });
    
    async function fetchPopularMovies() {
        try {
            // Fetch popular movies data
            const response = await fetch('/api/movies/popular');
            const data = await response.json();
            
            // If successful, render movies
            if (data && data.length > 0) {
                renderMovies(data);
            } else {
                showFallbackMovies();
            }
        } catch (error) {
            console.error('Error fetching movies:', error);
            showFallbackMovies();
        }
    }
    
    function renderMovies(movies) {
        const container = document.querySelector('.popular-movies');
        // Hide loading spinner
        document.querySelector('.loading-spinner').style.display = 'none';
        
        // Only use the first 4 movies
        const moviesToShow = movies.slice(0, 4);
        
        let html = '';
        moviesToShow.forEach(movie => {
            // Determine poster URL (handling both OMDB and TMDB formats)
            let posterUrl;
            if (movie.poster_path && movie.poster_path.startsWith('http')) {
                // OMDB full URL
                posterUrl = movie.poster_path;
            } else if (movie.poster_path) {
                // TMDB path
                posterUrl = 'https://image.tmdb.org/t/p/w500' + movie.poster_path;
            } else if (movie.Poster && movie.Poster !== 'N/A') {
                // OMDB Poster field
                posterUrl = movie.Poster;
            } else {
                posterUrl = '/images/no-poster.jpg';
            }
            
            // Get the correct movie ID
            const movieId = movie.id || movie.imdbID;
            
            // Get year from different possible formats
            let year = '';
            if (movie.release_date) {
                year = movie.release_date.substr(0, 4);
            } else if (movie.Year) {
                year = movie.Year;
            }
            
            // Get rating from different possible formats
            let rating = 'N/A';
            if (movie.vote_average) {
                rating = parseFloat(movie.vote_average).toFixed(1);
            } else if (movie.imdbRating) {
                rating = parseFloat(movie.imdbRating).toFixed(1);
            }
            
            html += `
                <div class="col-6 col-md-3">
                    <a href="/movie/${movieId}" class="text-decoration-none">
                        <div class="movie-suggestion-card">
                            <img src="${posterUrl}" class="w-100 movie-suggestion-img" alt="${movie.title || movie.Title}" loading="lazy">
                            <div class="p-3">
                                <h5 class="movie-suggestion-title">${movie.title || movie.Title}</h5>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">${year}</span>
                                    <span class="movie-suggestion-rating">
                                        <i class="bi bi-star-fill me-1"></i>${rating}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    function showFallbackMovies() {
        // Hide loading spinner
        document.querySelector('.loading-spinner').style.display = 'none';
        
        // Fallback movie data in case API fails - Using IMDB IDs for OMDB compatibility
        const fallbackMovies = [
            {
                id: 'tt0111161',
                title: 'The Shawshank Redemption',
                poster_path: 'https://m.media-amazon.com/images/M/MV5BMDFkYTc0MGEtZmNhMC00ZDIzLWFmNTEtODM1ZmRlYWMwMWFmXkEyXkFqcGdeQXVyMTMxODk2OTU@._V1_SX300.jpg',
                release_date: '1994-09-23',
                vote_average: 9.3
            },
            {
                id: 'tt0068646',
                title: 'The Godfather',
                poster_path: 'https://m.media-amazon.com/images/M/MV5BM2MyNjYxNmUtYTAwNi00MTYxLWJmNWYtYzZlODY3ZTk3OTFlXkEyXkFqcGdeQXVyNzkwMjQ5NzM@._V1_SX300.jpg',
                release_date: '1972-03-24',
                vote_average: 9.2
            },
            {
                id: 'tt0468569',
                title: 'The Dark Knight',
                poster_path: 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_SX300.jpg',
                release_date: '2008-07-18',
                vote_average: 9.0
            },
            {
                id: 'tt0050083',
                title: '12 Angry Men',
                poster_path: 'https://m.media-amazon.com/images/M/MV5BMWU4N2FjNzYtNTVkNC00NzQ0LTg0MjAtYTJlMjFhNGUxZDFmXkEyXkFqcGdeQXVyNjc1NTYyMjg@._V1_SX300.jpg',
                release_date: '1957-04-10',
                vote_average: 9.0
            }
        ];
        
        renderMovies(fallbackMovies);
    }
</script>
@endpush
@endsection