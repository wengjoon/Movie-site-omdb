@extends('layouts.app')

{{-- SEO Meta Tags --}}
@php
    $seoTitle = "Search Results for \"{$query}\" - 123 Movies Pro";
    $seoDescription = "Search results for {$query} - Watch free HD movies and TV shows on 123 Movies Pro. Stream online with no signup required.";
    $seoKeywords = "123 movies pro, {$query}, search results, free streaming, watch online, HD movies";
@endphp

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_keywords', $seoKeywords)
@section('canonical_url', route('movies.search', ['query' => $query]))

@section('content')
<div class="container mt-4">
    <div class="search-header">
        <h1>Results for "{{ $query }}"</h1>
        <form action="{{ route('movies.search') }}" method="get" class="mt-3">
            <div class="input-group">
                <input type="text" name="query" class="form-control search-input" value="{{ $query }}" placeholder="Search for movies...">
                <button class="btn btn-primary search-btn" type="submit">Search</button>
            </div>
        </form>
    </div>

    @if(count($results) > 0)
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            @foreach($results as $movie)
                <div class="col">
                    <div class="movie-card h-100">
                        <a href="{{ route('movies.show', ['id' => $movie['id']]) }}" class="text-decoration-none">
                            @if($movie['poster_path'])
                                <img src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}" class="card-img-top" alt="{{ $movie['title'] }} poster - Watch on 123 Movies Pro" loading="lazy">
                            @else
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <span class="text-light"><i class="bi bi-film" style="font-size: 3rem;"></i></span>
                                </div>
                            @endif
                            <div class="card-body">
                                <h5 class="movie-title" title="{{ $movie['title'] }}">{{ $movie['title'] }}</h5>
                                @if(isset($movie['release_date']) && !empty($movie['release_date']))
    <p class="movie-year">{{ substr($movie['release_date'], 0, 4) }}</p>
@endif
                                <p class="movie-rating">
    <i class="bi bi-star-fill me-1"></i> 
    {{ isset($movie['vote_average']) ? number_format($movie['vote_average'], 1) : 'N/A' }}/10
</p>
                                <div class="movie-hover-info">
                                    <div class="hover-buttons">
                                        <a href="{{ route('movies.show', ['id' => $movie['id']]) }}" class="btn btn-sm btn-danger">
                                            <i class="bi bi-info-circle me-1"></i> Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        @if($total_pages > 1)
            <div class="d-flex justify-content-center mt-5">
                <nav aria-label="Search results pages">
                    <ul class="pagination">
                        @if($current_page > 1)
                            <li class="page-item">
                                <a class="page-link" href="{{ route('movies.search', ['query' => $query, 'page' => $current_page - 1]) }}" rel="prev">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        @endif
                        
                        @php
                            $startPage = max(1, $current_page - 2);
                            $endPage = min($startPage + 4, $total_pages);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                        @endphp
                        
                        @for($i = $startPage; $i <= $endPage; $i++)
                            <li class="page-item {{ $i == $current_page ? 'active' : '' }}">
                                <a class="page-link" href="{{ route('movies.search', ['query' => $query, 'page' => $i]) }}">{{ $i }}</a>
                            </li>
                        @endfor
                        
                        @if($current_page < $total_pages)
                            <li class="page-item">
                                <a class="page-link" href="{{ route('movies.search', ['query' => $query, 'page' => $current_page + 1]) }}" rel="next">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    @else
        <div class="alert" style="background-color: rgba(22, 22, 22, 0.7); color: white; border: 1px solid rgba(255, 255, 255, 0.1);">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-circle me-3" style="font-size: 2rem;"></i>
                <div>
                    <h4 class="alert-heading">No Results Found</h4>
                    <p class="mb-0">We couldn't find any movies matching "{{ $query }}". Please try different keywords.</p>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .search-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1.2rem;
    }
    
    .movie-card {
        position: relative;
        overflow: hidden;
    }
    
    .movie-year {
        color: var(--netflix-light-gray);
        font-size: 0.9rem;
        margin-top: -0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .movie-hover-info {
        position: absolute;
        bottom: -50px;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
        padding: 15px;
        transition: all 0.3s ease;
        opacity: 0;
    }
    
    .movie-card:hover .movie-hover-info {
        bottom: 0;
        opacity: 1;
    }
    
    .hover-buttons {
        display: flex;
        justify-content: center;
    }
    
    .hover-buttons .btn-danger {
        background-color: var(--netflix-red);
        border: none;
    }
    
    .hover-buttons .btn-danger:hover {
        background-color: #f40612;
    }
    
    /* Pagination */
    .pagination {
        margin-top: 2rem;
    }
    
    .page-link {
        background-color: var(--netflix-light-dark);
        border-color: #333;
        color: var(--netflix-text);
        padding: 0.5rem 0.8rem;
    }
    
    .page-item.active .page-link {
        background-color: var(--netflix-red);
        border-color: var(--netflix-red);
    }
    
    .page-link:hover {
        background-color: rgba(229, 9, 20, 0.7);
        color: white;
    }
</style>
@endpush
@endsection