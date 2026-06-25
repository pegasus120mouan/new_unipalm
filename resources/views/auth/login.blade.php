<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Unipalm</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/pages/unipalm-auth.css') }}">
    @include('layout.partials.favicon')
</head>

<body>
    <div id="auth">
        <div class="row g-0">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <div class="auth-left-inner">
                        <div class="auth-logo">
                            <a href="{{ route('login') }}">
                                <img src="{{ asset('assets/images/logo/logo.png') }}" alt="Unipalm">
                            </a>
                        </div>

                        <div class="auth-form-header">
                            <h1 class="auth-title">Connexion</h1>
                            <p class="auth-subtitle">Accédez à votre espace de gestion des tickets de récolte.</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-exclamation-circle flex-shrink-0"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="auth-form">
                            @csrf

                            <div class="mb-4">
                                <label for="login" class="form-label auth-label">Identifiant</label>
                                <div class="form-group position-relative has-icon-left mb-0">
                                    <input type="text" name="login" id="login" value="{{ old('login') }}"
                                        class="form-control form-control-xl @error('login') is-invalid @enderror"
                                        placeholder="Saisissez votre identifiant" required autofocus autocomplete="username">
                                    <div class="form-control-icon">
                                        <i class="bi bi-person"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label auth-label">Mot de passe</label>
                                <div class="form-group position-relative has-icon-left mb-0">
                                    <input type="password" name="password" id="password"
                                        class="form-control form-control-xl @error('password') is-invalid @enderror"
                                        placeholder="Saisissez votre mot de passe" required autocomplete="current-password">
                                    <div class="form-control-icon">
                                        <i class="bi bi-lock"></i>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 auth-submit">
                                Se connecter
                                <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </form>

                        <p class="auth-footer mb-0">
                            &copy; {{ date('Y') }} Unipalm. Tous droits réservés.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right">
                    <div class="auth-brand-panel">
                        <div class="auth-brand-content">
                            <p class="brand-eyebrow">Société Coopérative Agricole</p>
                            <h2 class="brand-name">UNIPALM</h2>
                            <div class="brand-divider"></div>
                            <p class="brand-tagline">
                                Plateforme de gestion des tickets de récolte, suivi des agents et validation des livraisons.
                            </p>

                            <ul class="auth-features">
                                <li>
                                    <span class="feature-icon"><i class="bi bi-receipt"></i></span>
                                    <div>
                                        <strong>Tickets en temps réel</strong>
                                        <span>Enregistrement et suivi de chaque livraison</span>
                                    </div>
                                </li>
                                <li>
                                    <span class="feature-icon"><i class="bi bi-people"></i></span>
                                    <div>
                                        <strong>Agents &amp; usines</strong>
                                        <span>Gestion centralisée des ressources</span>
                                    </div>
                                </li>
                                <li>
                                    <span class="feature-icon"><i class="bi bi-patch-check"></i></span>
                                    <div>
                                        <strong>Validation sécurisée</strong>
                                        <span>Contrôle des prix unitaires et paiements</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
