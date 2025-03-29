<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    
    {{-- Include centralized SEO meta tags --}}
    @include('partials.seo-meta')
    
    {{-- Performance optimizations --}}
    <link rel="preconnect" href="https://api.themoviedb.org">
    <link rel="preconnect" href="https://image.tmdb.org">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    {{-- Styles with versioning --}}
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    {{-- Custom CSS with versioning (if present) --}}
    @if(file_exists(public_path('css/custom.css')))
        <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">
    @endif
    
    <style>
        :root {
            --netflix-red: #E50914;
            --netflix-dark: #141414;
            --netflix-light-dark: #181818;
            --netflix-darker: #0A0A0A;
            --netflix-text: #FFFFFF;
            --netflix-gray: #808080;
            --netflix-light-gray: #b3b3b3;
        }
        
        body {
            background-color: var(--netflix-dark);
            color: var(--netflix-text);
            font-family: 'Poppins', sans-serif;
            font-weight: 300;
        }
        
        .navbar {
            background-color: var(--netflix-darker);
            padding: 0.5rem 2rem;
            box-shadow: 0 1px 10px rgba(0, 0, 0, 0.7);
        }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--netflix-red) !important;
            letter-spacing: -1px;
            width: auto;
            white-space: nowrap;
            overflow: visible;
        }
        
        .nav-link {
            color: var(--netflix-text) !important;
            margin: 0 10px;
            font-weight: 500;
        }
        
        /* Search container styling */
        .search-container {
            background-color: var(--netflix-dark);
            position: relative;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), 
                              url('https://assets.nflxext.com/ffe/siteui/vlv3/ab4b0b22-2ddf-4d48-ae88-c201ae0267e2/0efe6360-4f6d-4b10-beb6-81e0762cfe81/US-en-20231030-popsignuptwoweeks-perspective_alpha_website_large.jpg');
            background-size: cover;
            background-position: center;
            text-align: center;
        }
        
        .search-container h1 {
            font-size: 3rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 1.5rem;
        }

        .search-container p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-box {
            width: 100%;
            max-width: 600px;
            position: relative;
            margin: 0 auto;
        }
        
        .search-input {
            height: 60px;
            background-color: rgba(22, 22, 22, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding-left: 20px;
            font-size: 1.2rem;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            background-color: rgba(22, 22, 22, 0.9);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.5);
            color: white;
        }
        
        .search-input::placeholder {
            color: var(--netflix-light-gray);
        }
        
        .search-btn {
            height: 60px;
            background-color: var(--netflix-red);
            border: none;
            font-size: 1.2rem;
            font-weight: 600;
            min-width: 120px;
        }
        
        .search-btn:hover {
            background-color: #f40612;
        }
        
        /* Movie cards styling */
        .movie-card {
            background-color: var(--netflix-light-dark);
            border: none;
            border-radius: 4px;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .movie-card:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            z-index: 1;
        }
        
        .movie-card a {
            text-decoration: none;
        }
        
        .card-img-top {
            height: 350px;
            object-fit: cover;
            border-bottom: 2px solid #333;
        }
        
        .movie-card .card-body {
            padding: 1rem;
            color: var(--netflix-text);
            background-color: var(--netflix-light-dark);
        }
        
        .movie-title {
            font-weight: 600;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: white;
        }
        
        .movie-directors, .movie-rating {
            font-size: 0.9rem;
            color: var(--netflix-light-gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .movie-rating {
            color: #FFC107;
            font-weight: 600;
        }
        
        /* Search results */
        .search-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .search-header h2 {
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .pagination {
            margin-top: 2rem;
        }
        
        .page-link {
            background-color: var(--netflix-light-dark);
            border-color: #333;
            color: var(--netflix-text);
        }
        
        .page-item.active .page-link {
            background-color: var(--netflix-red);
            border-color: var(--netflix-red);
        }
        
        .page-link:hover {
            background-color: #333;
            color: white;
        }
        
        /* Footer */
        footer {
            background-color: var(--netflix-darker);
            color: var(--netflix-light-gray);
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        footer h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 1.2rem;
        }
        
        footer ul {
            padding-left: 0;
        }
        
        footer ul li {
            margin-bottom: 0.5rem;
        }
        
        footer a {
            color: var(--netflix-light-gray);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: var(--netflix-red);
        }
        
        /* Breadcrumbs */
        .breadcrumb {
            background-color: transparent;
            padding: 0.75rem 0;
        }
        
        .breadcrumb-item a {
            color: var(--netflix-light-gray);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: white;
        }
        
        .breadcrumb-item+.breadcrumb-item::before {
            color: var(--netflix-gray);
        }
        
        /* Responsive adjustments */
        @media (max-width: 767px) {
            .search-container h1 {
                font-size: 2rem;
            }
            
            .search-container p {
                font-size: 1rem;
            }
            
            .search-input, .search-btn {
                height: 50px;
                font-size: 1rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('movies.index') }}" style="width: auto; white-space: nowrap; overflow: visible;">{{ config('seo.site_name') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('movies.index') }}">Home</a>
                    </li>
                                    </ul>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>{{ config('seo.site_name') }}</h5>
                    <p>The best free movie streaming site to watch movies online. Enjoy high quality content with no registration.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('movies.index') }}">Home</a></li>
                        <li><a href="{{ route('movies.search', ['query' => 'action']) }}">Action Movies</a></li>
                        <li><a href="{{ route('movies.search', ['query' => 'comedy']) }}">Comedy Movies</a></li>
                        <li><a href="{{ route('movies.search', ['query' => 'horror']) }}">Horror Movies</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Popular Movies</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('movies.search', ['query' => 'friends']) }}">Shawshank Redemption Movies</a></li>
                        <li><a href="{{ route('movies.search', ['query' => 'breaking bad']) }}">The Godfather Movies</a></li>
                        <li><a href="{{ route('movies.search', ['query' => 'game of thrones']) }}">Star Wars Movies</a></li>
                        <li><a href="{{ route('movies.search', ['query' => 'stranger things']) }}">Good Fellas Movies</a></li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; {{ date('Y') }} {{ config('seo.site_name') }} | {{ config('seo.footer_text') }}</p>
                </div>
            </div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    @stack('scripts')

    {{-- Structured data markup --}}
    @yield('structured_data')
</body>
</html>