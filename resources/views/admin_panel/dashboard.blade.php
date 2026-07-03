@extends('admin_panel.layout.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .welcome-container {
        min-height: calc(100vh - 150px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background-color: #f4f7f6;
        font-family: 'Inter', sans-serif;
        padding: 20px;
    }

    footer {
        display: none !important;
    }

    .welcome-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        padding: 50px;
        text-align: center;
        max-width: 600px;
        width: 100%;
        border-top: 5px solid #2d3e50;
    }

    .welcome-card img {
        max-height: 100px;
        margin-bottom: 30px;
    }

    .welcome-heading {
        color: #2d3e50;
        font-weight: 700;
        font-size: 2.2rem;
        margin-bottom: 5px;
        letter-spacing: -0.5px;
    }

    .welcome-subheading {
        color: #6c757d;
        font-size: 1.1rem;
        margin-bottom: 40px;
        font-weight: 400;
    }

    .clock-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
    }

    #time-display {
        font-size: 3.5rem;
        font-weight: 700;
        color: #2d3e50;
        margin: 0;
        line-height: 1;
    }

    #ampm {
        font-size: 1.2rem;
        color: #6c757d;
        margin-left: 5px;
        text-transform: uppercase;
        font-weight: 500;
    }

    #date-display {
        color: #495057;
        font-size: 1.1rem;
        margin-top: 10px;
        font-weight: 500;
    }

    .footer-credits {
        margin-top: 50px;
        text-align: center;
        color: #6c757d;
    }

    .dev-text {
        font-size: 0.95rem;
        margin-bottom: 5px;
    }

    .dev-text span {
        color: #2d3e50;
        font-weight: 700;
    }

    .contact-info {
        font-size: 0.9rem;
        margin: 8px 0;
    }

    .copyright-text {
        font-size: 0.85rem;
        margin-top: 15px;
        opacity: 0.8;
    }

    /* Responsiveness */
    @media (max-width: 768px) {
        .welcome-container {
            min-height: calc(100vh - 100px);
            padding: 15px;
        }
        .welcome-card {
            padding: 30px 15px;
        }
        .welcome-heading {
            font-size: 1.6rem;
        }
        #time-display {
            font-size: 2.2rem;
        }
        #ampm {
            font-size: 0.9rem;
        }
        .welcome-subheading {
            font-size: 0.95rem;
            margin-bottom: 25px;
        }
        .clock-box {
            padding: 15px;
        }
        .footer-credits {
            margin-top: 30px;
        }
    }

    @media (max-width: 480px) {
        .welcome-heading {
            font-size: 1.4rem;
        }
        #time-display {
            font-size: 1.8rem;
        }
        #date-display {
            font-size: 0.9rem;
        }
    }
</style>

<div class="main-content p-0">
    <div class="welcome-container">
        <div class="welcome-card">
            <img src="{{ asset('assets/images/WIJDAN-removebg-preview.png') }}" alt="Logo">
            
            <h1 class="welcome-heading">Welcome to</h1>
            <h2 class="welcome-heading" style="color: #007bff; margin-top: 0;">AL MADINA TRADERS</h2>
            <p class="welcome-subheading">Business Management Portal</p>

            <div class="clock-box">
                <h3 id="time-display">00:00:00 <span id="ampm">AM</span></h3>
                <div id="date-display">Monday, January 1, 2025</div>
            </div>
        </div>

        <div class="footer-credits">
            <div class="dev-text">Develop By: <span>ProWave Software Solutions</span></div>
            <div class="contact-info">+92 317 3836 223 | +92 317 3859 647</div>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        
        // 12-hour format logic
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        const strHours = String(hours).padStart(2, '0');

        document.getElementById('time-display').innerHTML = `${strHours}:${minutes}:${seconds} <span id="ampm">${ampm}</span>`;
        
        // Date formatting
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('date-display').textContent = now.toLocaleDateString('en-US', options);
    }

    setInterval(updateClock, 1000);
    updateClock();
</script>
@endsection
