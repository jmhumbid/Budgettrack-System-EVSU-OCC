<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BudgetTrack | EVSU–Ormoc</title>
    <link rel="icon" type="image/png" href="img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        maroon: '#800000',
                        'maroon-dark': '#5a0000',
                        'maroon-light': '#a00000',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'pulse-slow': 'pulse 3s infinite',
                        'bounce-subtle': 'bounceSubtle 2s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(-10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' },
                        },
                        bounceSubtle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .nav-blur {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .header-gradient {
            background: linear-gradient(135deg, var(--maroon), var(--maroon-light));
        }
        .login-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .login-btn:hover::before {
            left: 100%;
        }
        .login-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ffffff;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .nav-link:hover {
            color: #ffffff;
            transform: translateY(-2px);
        }
        .brand-text-white {
            color: #ffffff;
            font-weight: 800;
        }
        header {
            transition: transform 0.3s ease-in-out;
        }
        /* Responsive improvements */
        @media (max-width: 640px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .card {
                padding: 1.5rem;
            }
            .hero-content h1 {
                font-size: 2rem;
                line-height: 1.2;
            }
            .hero-content p {
                font-size: 0.95rem;
            }
        }
        /* Ensure cards are responsive */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-4px);
        }
        /* Mobile menu animation */
        #mobile-menu {
            max-height: 0;
            overflow: hidden;
        }
        #mobile-menu:not(.hidden) {
            max-height: 500px;
        }
    </style>
</head>
<body class="min-h-screen bg-fixed bg-cover bg-center" style="background-image: linear-gradient(rgba(255,255,255,.53), rgba(255,255,255,.53)), url('assets/img/bg.png');">
    <header class="fixed top-0 left-0 right-0 z-50 nav-blur header-gradient border-b border-white/20 shadow-lg">
        <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
            <nav class="flex items-center justify-between">
                <div class="flex items-center space-x-2 sm:space-x-4 flex-shrink-0">
                    <img src="img/evsu_logo.png" alt="EVSU Logo" class="h-12 sm:h-16 w-auto">
                    <span class="brand-text-white text-lg sm:text-2xl font-bold whitespace-nowrap">BudgetTrack</span>
                </div>
                
                <div class="hidden md:flex items-center space-x-6 lg:space-x-8">
                    <a href="#policies" class="nav-link text-white hover:text-white font-medium transition-all duration-300">Policies</a>
                    <a href="#help" class="nav-link text-white hover:text-white font-medium transition-all duration-300">Help</a>
                    <a href="#contact" class="nav-link text-white hover:text-white font-medium transition-all duration-300">Contacts</a>
                    <a href="login.php" class="login-btn px-4 lg:px-6 py-2 lg:py-3 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 text-sm lg:text-base">
                        <span class="relative z-10">Login</span>
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-white/50" aria-label="Toggle menu">
                    <svg id="menu-icon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </nav>
            
            <!-- Mobile menu -->
            <div id="mobile-menu" class="md:hidden mt-4 pb-4 border-t border-white/20 hidden transition-all duration-300">
                <div class="flex flex-col space-y-3 pt-4">
                    <a href="#policies" class="nav-link text-white hover:text-white font-medium transition-all duration-300 py-2 px-2 rounded hover:bg-white/10">Policies</a>
                    <a href="#help" class="nav-link text-white hover:text-white font-medium transition-all duration-300 py-2 px-2 rounded hover:bg-white/10">Help</a>
                    <a href="#contact" class="nav-link text-white hover:text-white font-medium transition-all duration-300 py-2 px-2 rounded hover:bg-white/10">Contacts</a>
                    <a href="login.php" class="login-btn px-6 py-3 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 text-center mt-2">
                        <span class="relative z-10">Login</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main class="pt-16 sm:pt-20">
        <section class="hero">
            <div class="hero-background-carousel">
                <div class="hero-bg-slide active" style="background-image: url('img/budget1.jpg'); background-size: cover; background-position: center;"></div>
                <div class="hero-bg-slide" style="background-image: url('img/budget2.jpg'); background-size: cover; background-position: center;"></div>
                <div class="hero-bg-slide" style="background-image: url('img/budget3.jpg'); background-size: cover; background-position: center;"></div>
            </div>
            <div class="hero-split-overlay"></div>
            <div class="container px-4 sm:px-6">
                <div class="hero-content">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl">Your Centralized Budget Monitoring System</h1>
                    <p class="text-base sm:text-lg lg:text-xl mt-4 sm:mt-6 max-w-3xl">BudgetTrack provides real-time visibility into budget allocations, expenditures, and remaining balances for EVSU-Ormoc Campus departments. Streamline financial monitoring with automated updates and comprehensive reporting.</p>
                    <a href="#policies" class="cta-btn mt-6 sm:mt-8 inline-block">Learn More</a>
                </div>
            </div>
        </section>
        <section id="policies" class="section">
            <div class="container px-4 sm:px-6">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl">Policies</h2>
                <p class="lead text-base sm:text-lg mt-4">Core governance references embedded into BudgetTrack for compliance and transparency.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-8 sm:mt-12">
                    <div class="card">
                        <div class="card-icon">📘</div>
                        <h3>CHED CMO-No.20-S2011</h3>
                        <p>Standard allocation categories (Instruction, Research, Extension, Production, Administrative) used for tagging and reporting.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">📋</div>
                        <h3>CHED–DBM JMC 2017-1A</h3>
                        <p>Reallocation rules with justification and approvals. The system records requests and preserves an audit trail.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">📑</div>
                        <h3>RA 9184 (GPRA)</h3>
                        <p>PPMP → APP → PR workflow with multi-stage reviews to ensure economy, efficiency, and accountability.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🏷️</div>
                        <h3>Funds & Tagging</h3>
                        <p>Fiduciary, Non‑Fiduciary, and TOSI funds supported with usage restrictions reflected across modules.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🔐</div>
                        <h3>Role‑Based Access</h3>
                        <p>Admin, Budget Staff, Department Head, and Department User roles restrict actions to responsibilities.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🧾</div>
                        <h3>Data Privacy</h3>
                        <p>Accounts and financial records are protected; access is logged for audit trail and accountability.</p>
                    </div>
                </div>
            </div>
        </section>
        <section id="help" class="section help-section">
            <div class="container px-4 sm:px-6">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl">Help</h2>
                <p class="lead text-base sm:text-lg mt-4">Quick guidance for getting started with BudgetTrack.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-8 sm:mt-12">
                    <div class="card">
                        <div class="card-icon">📝</div>
                        <h3>Registration</h3>
                        <p>Department heads: Your EVSU email must be registered first to access the system. Please reach out to the Budget Office for registration assistance.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🔑</div>
                        <h3>Login & Roles</h3>
                        <p>Admins manage accounts and roles. Department users can view budgets, heads can submit PPMP monitor utilization, and track Purchase Requests.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">📊</div>
                        <h3>Dashboard</h3>
                        <p>Real-time balances, allocations, and spending progress by department and category.</p>
                    </div>
                    <div class="card lg:col-start-2">
                        <div class="card-icon">🧮</div>
                        <h3>Reports</h3>
                        <p>Generate activity summaries with filters by fiscal year, department, and date range.</p>
                    </div>
                </div>
            </div>
        </section>
        <section id="contact" class="section">
            <div class="container px-4 sm:px-6">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl">Contact</h2>
                <p class="lead text-base sm:text-lg mt-4">Get in touch with the Budget Office for support and coordination.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-8 sm:mt-12">
                    <div class="card">
                        <div class="card-icon">🏛️</div>
                        <h3>Office</h3>
                        <p>Budget Office, EVSU–Ormoc Campus<br/>Mon–Fri, 8:00 AM – 5:00 PM</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">✉️</div>
                        <h3>Email</h3>
                        <p>budgetoffice@evsu-oc.edu.ph</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🌐</div>
                        <h3>Portal</h3>
                        <p>Access the system on campus network using your assigned credentials.</p>
                    </div>
                </div>
            </div>
        </section>
        <section class="section">
            <div class="container px-4 sm:px-6">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl">Key Features</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-8 sm:mt-12">
                    <div class="card">
                        <div class="card-icon">💰</div>
                        <h3>Budget Allocation</h3>
                        <p>Easily manage and track budget allocations across departments with real-time updates.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">📈</div>
                        <h3>Financial Reports</h3>
                        <p>Generate comprehensive reports for compliance, transparency, and informed decision-making processes.</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">🔒</div>
                        <h3>Secure Access</h3>
                        <p>Role-based access control ensures data security while maintaining transparency for authorized users.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer class="footer">
        <div class="container px-4 sm:px-6">
            <div class="text-center sm:text-left text-sm sm:text-base mb-2">© <span id="y"></span> EVSU–Ormoc Campus • BudgetTrack</div>
            <div class="text-center sm:text-left text-xs sm:text-sm">Contact us: <span style="color:#800000; font-weight:700;">skypian@gmail.com</span> / <span style="font-weight:700;">EVSU OCC</span></div>
        </div>
    </footer>
    <script src="js/main.js"></script>
    <script>
        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            
            mobileMenuBtn.addEventListener('click', function() {
                const isHidden = mobileMenu.classList.contains('hidden');
                const menuIcon = document.getElementById('menu-icon');
                
                if (isHidden) {
                    mobileMenu.classList.remove('hidden');
                    menuIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
                } else {
                    mobileMenu.classList.add('hidden');
                    menuIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
                }
            });
            
            // Close mobile menu when clicking on a link
            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.add('hidden');
                    const menuIcon = document.getElementById('menu-icon');
                    menuIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
                });
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideNav = event.target.closest('header');
                const isMenuOpen = !mobileMenu.classList.contains('hidden');
                
                if (!isClickInsideNav && isMenuOpen) {
                    mobileMenu.classList.add('hidden');
                    const menuIcon = document.getElementById('menu-icon');
                    menuIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
                }
            });
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Add scroll effect to navbar
            let lastScrollTop = 0;
            const header = document.querySelector('header');
            
            window.addEventListener('scroll', function() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    // Scrolling down
                    header.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up
                    header.style.transform = 'translateY(0)';
                }
                
                lastScrollTop = scrollTop;
            });
            
            // Preload background images
            const imageUrls = ['img/budget1.jpg', 'img/budget2.jpg', 'img/budget3.jpg'];
            imageUrls.forEach(url => {
                const img = new Image();
                img.src = url;
            });
            
            // Background image carousel
            const slides = document.querySelectorAll('.hero-bg-slide');
            let currentSlide = 0;
            
            // Ensure first slide is visible
            if (slides.length > 0) {
                slides[0].classList.add('active');
            }
            
            function nextSlide() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }
            
            // Switch images every 5 seconds
            setInterval(nextSlide, 5000);
        });
    </script>
    <?php include __DIR__ . '/components/footer.php'; ?>
</body>
</html>