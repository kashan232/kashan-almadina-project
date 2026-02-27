<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AL Madina Traders - Premium Battery Solutions</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.1;
        }
        
        .bg-animation span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.5);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }
        
        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        .bg-animation span:nth-child(1) { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .bg-animation span:nth-child(2) { left: 10%; width: 20px; height: 20px; animation-delay: 2s; }
        .bg-animation span:nth-child(3) { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .bg-animation span:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; }
        .bg-animation span:nth-child(5) { left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
        .bg-animation span:nth-child(6) { left: 75%; width: 110px; height: 110px; animation-delay: 3s; }
        .bg-animation span:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .bg-animation span:nth-child(8) { left: 50%; width: 25px; height: 25px; animation-delay: 15s; }
        .bg-animation span:nth-child(9) { left: 20%; width: 15px; height: 15px; animation-delay: 2s; }
        .bg-animation span:nth-child(10) { left: 85%; width: 150px; height: 150px; animation-delay: 0s; }
        
        /* Main Container */
        .container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* Hero Card */
        .hero-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 60px 40px;
            max-width: 900px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideUp 0.8s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Logo/Icon */
        .logo-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 20px;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Company Name */
        .company-name {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        /* Tagline */
        .tagline {
            font-size: 22px;
            color: #666;
            font-weight: 500;
            margin-bottom: 30px;
        }
        
        /* Description */
        .description {
            font-size: 16px;
            color: #777;
            line-height: 1.8;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Services */
        .services {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        
        .service-item {
            flex: 1;
            min-width: 200px;
            padding: 25px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .service-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .service-item i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .service-item h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .service-item p {
            font-size: 14px;
            color: #666;
        }
        
        /* Login Button */
        .login-btn {
            display: inline-block;
            padding: 18px 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .login-btn:hover::before {
            left: 100%;
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        .login-btn i {
            margin-left: 10px;
        }
        
        /* Footer */
        .footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        
        .footer p {
            color: #999;
            font-size: 14px;
            margin: 5px 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-card {
                padding: 40px 25px;
            }
            
            .company-name {
                font-size: 36px;
            }
            
            .tagline {
                font-size: 18px;
            }
            
            .services {
                flex-direction: column;
            }
            
            .service-item {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    
    <!-- Main Container -->
    <div class="container">
        <div class="hero-card">
            <!-- Logo/Icon -->
            <div class="logo-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            
            <!-- Company Name -->
            <h1 class="company-name">AL MADINA TRADERS</h1>
            
            <!-- Tagline -->
            <p class="tagline">Premium Battery Solutions</p>
            
            <!-- Description -->
            <p class="description">
                Your trusted partner for high-quality batteries and power solutions. We specialize in UPS batteries, 
                Solar batteries, and complete power backup systems. Delivering excellence in energy storage solutions 
                for homes, businesses, and industrial applications.
            </p>
            
            <!-- Services -->
            <div class="services">
                <div class="service-item">
                    <i class="bi bi-battery-charging"></i>
                    <h3>UPS Batteries</h3>
                    <p>Reliable power backup solutions</p>
                </div>
                
                <div class="service-item">
                    <i class="bi bi-sun-fill"></i>
                    <h3>Solar Batteries</h3>
                    <p>Eco-friendly energy storage</p>
                </div>
                
                <div class="service-item">
                    <i class="bi bi-tools"></i>
                    <h3>Installation</h3>
                    <p>Expert setup & maintenance</p>
                </div>
            </div>
            
            <!-- Login Button -->
            <a href="{{ route('login') }}" class="login-btn">
                Login to Dashboard
                <i class="bi bi-arrow-right-circle"></i>
            </a>
            
            <!-- Footer -->
            <div class="footer">
                <p><strong>AL Madina Traders</strong></p>
                <p>© {{ date('Y') }} All Rights Reserved</p>
            </div>
        </div>
    </div>
</body>
</html>
