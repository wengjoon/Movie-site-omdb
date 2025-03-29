@extends('layouts.app')
@if(!isset($movie['title']))
    @php abort(404) @endphp
@endif
{{-- SEO Meta Tags: Dynamic for movie details with movie title first --}}
@php
    $movieTitle = $movie['title'] ?? 'Movie';
    $releaseYear = isset($movie['release_date']) ? ' (' . substr($movie['release_date'], 0, 4) . ')' : '';
    
    // Title format: "Movie Title (Year)" - site name at the end
    $seoTitle = $movieTitle . $releaseYear . ' - Watch Free HD';
    
    // Description focusing on the movie first
    $overview = isset($movie['overview']) ? str_replace('"', "'", mb_substr($movie['overview'], 0, 155)) . '...' : '';
    $seoDescription = "{$movieTitle}{$releaseYear}: {$overview} Watch free on 123 Movies Pro.";
    
    $genres = [];
    if(isset($movie['genres'])) {
        foreach($movie['genres'] as $genre) {
            if(is_array($genre)) {
                $genres[] = $genre['name'];
            } else {
                $genres[] = $genre;
            }
        }
    }
    
    // Keywords with movie title first
    $keywords = "{$movieTitle}, " . implode(', ', $genres) . ", 123 movies pro, watch free, stream online, HD quality";
    
    // Handle the poster path for OMDB or TMDB
    $posterImg = '';
    if(isset($movie['poster_path']) && strpos($movie['poster_path'], 'http') === 0) {
        // OMDB full URL
        $posterImg = $movie['poster_path'];
    } elseif(isset($movie['poster_path'])) {
        // TMDB path
        $posterImg = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
    } elseif(isset($movie['Poster']) && $movie['Poster'] !== 'N/A') {
        // OMDB Poster field
        $posterImg = $movie['Poster'];
    } else {
        // Default image
        $posterImg = asset(config('seo.default_og_image'));
    }
    
    // Use the poster as backdrop if no backdrop is available
    $backdropImg = $posterImg;
    if(!empty($movie['backdrop_path'])) {
        $backdropImg = 'https://image.tmdb.org/t/p/original' . $movie['backdrop_path'];
    }
    
    // Rating values for schema markup
    $ratingValue = number_format($movie['vote_average'] ?? $movie['imdbRating'] ?? 0, 1);
    $ratingCount = $movie['vote_count'] ?? (isset($movie['imdbVotes']) ? str_replace(',', '', $movie['imdbVotes']) : 0);
@endphp

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_keywords', $keywords)
@section('og_type', 'video.movie')
@section('og_image', $posterImg)
@section('twitter_image', $posterImg)

{{-- Structured data for movie with enhanced AggregateRating --}}
@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Movie",
  "name": "{{ $movie['title'] ?? '' }}",
  "description": "{{ $movie['overview'] ?? 'Watch this movie online for free in HD quality.' }}",
  "image": "{{ $posterImg }}",
  @if(!empty($movie['release_date']) || !empty($movie['Released']))
  "datePublished": "{{ !empty($movie['release_date']) ? $movie['release_date'] : $movie['Released'] }}",
  @endif
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ $ratingValue }}",
    "bestRating": "10",
    "worstRating": "0",
    "ratingCount": "{{ $ratingCount }}",
    "reviewCount": "{{ $ratingCount }}"
  },
  @if(!empty($movie['directors']))
  "director": [
    @foreach($movie['directors'] as $index => $director)
    {
      "@type": "Person",
      "name": "{{ $director }}"
    }@if($index < count($movie['directors']) - 1),@endif
    @endforeach
  ],
  @endif
  @if(!empty($movie['top_cast']))
  "actor": [
    @foreach($movie['top_cast'] as $index => $actor)
    {
      "@type": "Person",
      "name": "{{ $actor }}"
    }@if($index < count($movie['top_cast']) - 1),@endif
    @endforeach
  ],
  @endif
  @if(isset($movie['genres']) && count($movie['genres']) > 0)
  "genre": [
    @foreach($movie['genres'] as $index => $genre)
    "{{ is_array($genre) ? $genre['name'] : $genre }}"@if($index < count($movie['genres']) - 1),@endif
    @endforeach
  ],
  @endif
  "potentialAction": {
    "@type": "WatchAction",
    "target": "{{ url()->current() }}"
  }
}
</script>

{{-- Additionally adding review schema if reviews exist --}}
@if(isset($movie['reviews']) && count($movie['reviews']) > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Movie",
  "name": "{{ $movie['title'] ?? '' }}",
  "review": [
    @foreach($movie['reviews'] as $index => $review)
    {
      "@type": "Review",
      "reviewBody": "{{ str_replace('"', "'", $review['content']) ?? '' }}",
      "author": {
        "@type": "Person",
        "name": "{{ $review['author'] ?? 'Anonymous Reviewer' }}"
      },
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "{{ $review['rating'] ?? $movie['vote_average'] ?? $movie['imdbRating'] ?? 5 }}",
        "bestRating": "10",
        "worstRating": "0"
      },
      "datePublished": "{{ $review['created_at'] ?? date('Y-m-d') }}"
    }@if($index < count($movie['reviews']) - 1),@endif
    @endforeach
  ]
}
</script>
@endif
@endsection

@section('content')
{{-- Document title is just the movie name for browsers --}}
<title>{{ $movieTitle }}{{ $releaseYear }}</title>

{{-- Breadcrumbs navigation --}}
<div class="container">
    <nav aria-label="breadcrumb" class="mt-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('movies.index') }}">Home</a></li>
            @if(isset($movie['genres']) && count($movie['genres']) > 0)
                <li class="breadcrumb-item">
                    <a href="{{ route('movies.search', ['query' => is_array($movie['genres'][0]) ? $movie['genres'][0]['name'] : $movie['genres'][0] ]) }}">
                        {{ is_array($movie['genres'][0]) ? $movie['genres'][0]['name'] : $movie['genres'][0] }}
                    </a>
                </li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $movie['title'] }}</li>
        </ol>
    </nav>
</div>

<!-- Hero Section with Netflix-style Backdrop using the Poster -->
<div class="movie-hero" style="background-image: url('{{ $backdropImg }}');">
    <div class="backdrop-overlay"></div>
    <div class="container hero-content">
        <div class="row">
            <div class="col-md-8">
                <h1 class="movie-hero-title">{{ $movie['title'] }}</h1>
                
                <div class="movie-meta mb-3">
                    @if(isset($movie['release_date']) && !empty($movie['release_date']))
                        <span class="movie-year">{{ \Carbon\Carbon::parse($movie['release_date'])->format('Y') }}</span>
                    @elseif(isset($movie['Year']) && !empty($movie['Year']))
                        <span class="movie-year">{{ $movie['Year'] }}</span>
                    @endif
                    
                    @if(isset($movie['runtime']) && $movie['runtime'] > 0)
                        <span class="movie-runtime">{{ floor($movie['runtime'] / 60) }}h {{ $movie['runtime'] % 60 }}m</span>
                    @elseif(isset($movie['Runtime']) && $movie['Runtime'] !== 'N/A')
                        <span class="movie-runtime">{{ $movie['Runtime'] }}</span>
                    @endif
                    
                    <span class="movie-rating-badge">
                        <i class="bi bi-star-fill me-1"></i> {{ $ratingValue }}
                    </span>
                </div>
                
                @if(isset($movie['tagline']) && !empty($movie['tagline']))
                    <p class="movie-tagline">{{ $movie['tagline'] }}</p>
                @endif
                
                <div class="movie-overview-hero mb-4">
                    @if(isset($movie['overview']) && !empty($movie['overview']))
                        {{ $movie['overview'] }}
                    @elseif(isset($movie['Plot']) && $movie['Plot'] !== 'N/A')
                        {{ $movie['Plot'] }}
                    @else
                        No description available.
                    @endif
                </div>
                
                <div class="movie-credits-hero mb-4">
                    @if(!empty($movie['directors']))
                        <div class="credit-row">
                            <span class="credit-title">Director{{ count($movie['directors']) > 1 ? 's' : '' }}:</span>
                            <span class="credit-people">{{ implode(', ', $movie['directors']) }}</span>
                        </div>
                    @elseif(isset($movie['Director']) && $movie['Director'] !== 'N/A')
                        <div class="credit-row">
                            <span class="credit-title">Director{{ strpos($movie['Director'], ',') !== false ? 's' : '' }}:</span>
                            <span class="credit-people">{{ $movie['Director'] }}</span>
                        </div>
                    @endif
                    
                    @if(!empty($movie['top_cast']))
                        <div class="credit-row">
                            <span class="credit-title">Starring:</span>
                            <span class="credit-people">{{ implode(', ', $movie['top_cast']) }}</span>
                        </div>
                    @elseif(isset($movie['Actors']) && $movie['Actors'] !== 'N/A')
                        <div class="credit-row">
                            <span class="credit-title">Starring:</span>
                            <span class="credit-people">{{ $movie['Actors'] }}</span>
                        </div>
                    @endif
                </div>
                
                
                <div class="hero-buttons">
                @if(config('site.show_watch_button', true))
                    <button id="playTrailerBtn" class="btn btn-danger btn-lg me-2">
                        <i class="bi bi-play-fill me-2"></i> Watch Now
                    </button>
                    @endif
                    
                    <a href="#" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-plus-lg me-2"></i> Add to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Movie Details Section -->
<div class="container movie-details">
    <div class="row">
        <!-- Left Column - Poster and Ratings -->
        <div class="col-md-4 mb-4">
            <div class="detail-poster-container">
                <!-- Display poster image -->
                <img src="{{ $posterImg }}" class="img-fluid movie-poster" alt="{{ $movie['title'] }}" loading="lazy">
                
                <!-- Aggregate rating display -->
                <div class="rating-container mt-3">
                    <div class="aggregate-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                        <meta itemprop="worstRating" content="0">
                        <meta itemprop="bestRating" content="10">
                        <meta itemprop="ratingValue" content="{{ $ratingValue }}">
                        <meta itemprop="ratingCount" content="{{ $ratingCount }}">
                        <meta itemprop="reviewCount" content="{{ $ratingCount }}">
                        
                        <div class="rating-display text-center">
                            <div class="rating-stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= round($ratingValue / 2))
                                        <i class="bi bi-star-fill text-warning"></i>
                                    @elseif ($i - 0.5 <= $ratingValue / 2)
                                        <i class="bi bi-star-half text-warning"></i>
                                    @else
                                        <i class="bi bi-star text-warning"></i>
                                    @endif
                                @endfor
                            </div>
                            <div class="rating-value fs-4 fw-bold">
                                {{ $ratingValue }}/10
                            </div>
                            <div class="rating-count text-white">
    Based on {{ number_format($ratingCount) }} ratings
</div>
                        </div>
                    </div>
                </div>
                
                <!-- OMDB Ratings Section -->
                @if(isset($movie['ratings']) && is_array($movie['ratings']) && count($movie['ratings']) > 0)
                <div class="mt-4 ratings-section">
                    <h5 class="mb-3">Ratings</h5>
                    @foreach($movie['ratings'] as $rating)
                        <div class="rating-card p-3 text-center mb-3">
                            <h6 class="mb-2">{{ $rating['Source'] }}</h6>
                            <div class="rating-value">{{ $rating['Value'] }}</div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column - Details and Info -->
        <div class="col-md-8">
            <!-- Genre badges -->
            <div class="section-title">Genres</div>
            <div class="movie-genres mb-4">
                @if(isset($movie['genres']) && count($movie['genres']) > 0)
                    @foreach($movie['genres'] as $genre)
                        <a href="{{ route('movies.search', ['query' => is_array($genre) ? $genre['name'] : $genre]) }}" class="genre-badge">
                            {{ is_array($genre) ? $genre['name'] : $genre }}
                        </a>
                    @endforeach
                @elseif(isset($movie['Genre']) && $movie['Genre'] !== 'N/A')
                    @foreach(explode(', ', $movie['Genre']) as $genre)
                        <a href="{{ route('movies.search', ['query' => $genre]) }}" class="genre-badge">{{ $genre }}</a>
                    @endforeach
                @endif
            </div>
            
            <!-- Additional Writer info -->
            @if(isset($movie['Writer']) && $movie['Writer'] !== 'N/A')
                <div class="section-title">Writers</div>
                <div class="info-section mb-4">
                    <p class="mb-0">{{ $movie['Writer'] }}</p>
                </div>
            @endif
            
            <!-- Additional Movie Info Sections -->
            <div class="section-title">Details</div>
            <div class="info-section mb-4">
                <div class="row">
                    @if(isset($movie['Production']) && $movie['Production'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
                            <h6>Production</h6>
                            <p class="mb-0">{{ $movie['Production'] }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($movie['Country']) && $movie['Country'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
                            <h6>Country</h6>
                            <p class="mb-0">{{ $movie['Country'] }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($movie['Language']) && $movie['Language'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
                            <h6>Language</h6>
                            <p class="mb-0">{{ $movie['Language'] }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($movie['BoxOffice']) && $movie['BoxOffice'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
                            <h6>Box Office</h6>
                            <p class="mb-0">{{ $movie['BoxOffice'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            @if(isset($movie['Awards']) && $movie['Awards'] !== 'N/A')
            <div class="section-title">Awards</div>
            <div class="info-section mb-4">
                <p class="mb-0">{{ $movie['Awards'] }}</p>
            </div>
            @endif
            
            <!-- Social Sharing Buttons -->
            <div class="section-title">Share</div>
            <div class="social-share mb-4">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-primary me-2">
                    <i class="bi bi-facebook"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?text=Watch {{ urlencode($movie['title']) }}&url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-info me-2">
                    <i class="bi bi-twitter"></i> Twitter
                </a>
                <a href="https://wa.me/?text=Watch {{ urlencode($movie['title']) }}: {{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-success">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>
    
    <!-- User reviews section if available -->
    @if(isset($movie['reviews']) && count($movie['reviews']) > 0)
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="section-title-large mb-4">User Reviews</h2>
            
            <div class="reviews-container">
                @foreach($movie['reviews'] as $review)
                <div class="review-card mb-4" itemprop="review" itemscope itemtype="https://schema.org/Review">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                                    <span itemprop="name" class="fw-bold">{{ $review['author'] ?? 'Anonymous' }}</span>
                                </span>
                                <meta itemprop="datePublished" content="{{ $review['created_at'] ?? date('Y-m-d') }}">
                                <span class="text-muted ms-2">{{ isset($review['created_at']) ? \Carbon\Carbon::parse($review['created_at'])->format('M d, Y') : date('M d, Y') }}</span>
                            </div>
                            <div class="review-rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                                <meta itemprop="worstRating" content="0">
                                <meta itemprop="bestRating" content="10">
                                <meta itemprop="ratingValue" content="{{ $review['rating'] ?? $movie['vote_average'] ?? $movie['imdbRating'] ?? 5 }}">
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill me-1"></i>
                                    {{ number_format($review['rating'] ?? $movie['vote_average'] ?? $movie['imdbRating'] ?? 5, 1) }}/10
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p itemprop="reviewBody" class="mb-0">{{ $review['content'] ?? 'No content available.' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Trailer Modal -->
<div class="modal fade" id="trailerModal" tabindex="-1" aria-labelledby="trailerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trailerModalLabel">{{ $movie['title'] }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="trailerIframe" src="about:blank" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Hero Section - Netflix Style */
    .movie-hero {
        position: relative;
        min-height: 80vh;
        background-size: cover;
        background-position: center;
        margin-top: -24px;
        color: white;
        display: flex;
        align-items: center;
    }
    
    .backdrop-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.7) 30%, rgba(0,0,0,0.4) 60%, rgba(0,0,0,0.2) 100%),
                    linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 20%, rgba(0,0,0,0.4) 40%, rgba(0,0,0,0) 60%);
        z-index: 1;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        padding: 3rem 0;
    }
    
    .movie-hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .movie-overview-hero {
        font-size: 1.1rem;
        max-width: 700px;
        line-height: 1.6;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
    }
    
    .movie-tagline {
        font-size: 1.4rem;
        font-style: italic;
        margin-bottom: 1.5rem;
        color: #e5e5e5;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
    }
    
    .hero-buttons .btn {
        padding: 0.75rem 1.5rem;
        font-weight: 600;
    }
    
    .hero-buttons .btn-danger {
        background-color: var(--netflix-red);
        border: none;
    }
    
    .movie-credits-hero .credit-row {
        margin-bottom: 0.5rem;
    }
    
    .movie-credits-hero .credit-title {
        color: #ccc;
        margin-right: 0.5rem;
    }
    
    .movie-credits-hero .credit-people {
        color: white;
        font-weight: 500;
    }
    
    /* Movie meta (year, runtime, rating) */
    .movie-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .movie-year, .movie-runtime {
        color: #e5e5e5;
    }
    
    .movie-rating-badge {
        background-color: var(--netflix-red);
        color: white;
        padding: 0.3rem 0.7rem;
        border-radius: 4px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }
    
    /* Movie Details Section */
    .movie-details {
        padding: 3rem 0;
    }
    
    .detail-poster-container {
        margin-top: 1rem;
    }
    
    .movie-poster {
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
        width: 100%;
        height: auto;
        object-fit: cover;
    }
    
    /* Section titles */
    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: white;
        border-left: 4px solid var(--netflix-red);
        padding-left: 0.8rem;
    }
    
    .section-title-large {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: white;
        border-left: 5px solid var(--netflix-red);
        padding-left: 1rem;
    }
    
    /* Rating display */
    .rating-container {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .rating-stars {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .rating-value {
        color: #FFC107;
    }
    
    .rating-count {
        font-size: 0.9rem;
    }
    
    /* Genre badges */
    .movie-genres {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .genre-badge {
        background-color: rgba(255, 255, 255, 0.1);
        color: #e5e5e5;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .genre-badge:hover {
        background-color: var(--netflix-red);
        color: white;
    }
    
    /* Info sections */
    .info-section {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        padding: 1.2rem;
        margin-bottom: 2rem;
    }
    
    .info-item h6 {
        color: #aaa;
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }
    
    .info-item p {
        color: #e5e5e5;
        font-size: 1rem;
    }
    
    /* OMDB Ratings Section */
    .ratings-section h5 {
        color: #e5e5e5;
        font-weight: 600;
    }
    
    .rating-card {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .rating-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .rating-card h6 {
        color: #aaa;
        font-size: 0.9rem;
    }
    
    .rating-card .rating-value {
        font-size: 1.3rem;
        font-weight: 700;
    }
    
    /* Social sharing buttons */
    .social-share a {
        transition: all 0.3s ease;
    }
    
    .social-share a:hover {
        transform: translateY(-3px);
    }
    
    /* User review section */
    .review-card .card {
        background-color: var(--netflix-light-dark);
        border: none;
    }
    
    .review-card .card-header {
        background-color: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    /* Breadcrumbs */
    .breadcrumb {
        padding: 0.75rem 0;
        margin-bottom: 0;
        background-color: transparent;
        z-index: 100;
        position: relative;
    }
    
    .breadcrumb-item a {
        color: var(--netflix-light-gray);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-item a:hover {
        color: var(--netflix-red);
    }
    
    .breadcrumb-item.active {
        color: white;
    }
    
    /* Modal */
    #trailerModal .modal-content {
        background-color: #000;
        border: none;
    }
    
    #trailerModal .modal-header {
        border-bottom: 1px solid #333;
        background-color: #111;
        color: white;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 767px) {
        .movie-hero {
            min-height: 100vh;
        }
        
        .movie-hero-title {
            font-size: 2.5rem;
        }
        
        .movie-tagline {
            font-size: 1.2rem;
        }
        
        .movie-overview-hero {
            font-size: 1rem;
        }
        
        .backdrop-overlay {
            background: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.8) 100%),
                         linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.5) 50%, rgba(0,0,0,0.3) 100%);
        }
        
        .hero-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }
        
        .hero-buttons .btn {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const playTrailerBtn = document.getElementById('playTrailerBtn');
        const trailerIframe = document.getElementById('trailerIframe');
        const trailerModal = new bootstrap.Modal(document.getElementById('trailerModal'));
        
        const playTrailer = function() {
            // For OMDB API, we'll use the IMDb ID to find trailers
            const imdbId = '{{ $movie['id'] }}';
            // Set the iframe src with the IMDb ID
            trailerIframe.src = `https://autoembed.co/movie/imdb/${imdbId}`;
            trailerModal.show();
        };
        
        playTrailerBtn.addEventListener('click', playTrailer);
        
        // When modal is hidden, stop the video by setting iframe src to blank
        document.getElementById('trailerModal').addEventListener('hidden.bs.modal', function() {
            trailerIframe.src = 'about:blank';
        });
    });
</script>
@endpush
@endsection