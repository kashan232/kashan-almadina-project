<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | ALmadina Bettery</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Animation -->
    <style>
        body {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-card {
            animation: fadeIn 0.8s ease-in-out;
            transition: transform 0.3s;
        }
        .reset-card:hover {
            transform: scale(1.02);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card reset-card shadow-sm border-0 p-4">
                <div class="card-body">
                    <h3 class="card-title text-center mb-3">🔐 Forgot Password</h3>
                    <p class="text-muted text-center mb-4">
                        No problem! Just enter your email and we
                    </p>

              <form method="POST" action="{{ route('password.email') }}">
        @csrf


                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="Enter your registered email" required>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Send Reset Link</button>
                        </div>

                        <!-- Back to login -->
                        <div class="mt-3 text-center">
                            <a href="/login" class="text-decoration-none">Back to login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
