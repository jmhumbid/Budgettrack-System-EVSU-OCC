<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BudgetTrack | Forgot Password</title>
    <link rel="icon" type="image/png" href="img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/login.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script crossorigin src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const year = document.getElementById('year');
            if (year) year.textContent = new Date().getFullYear();
        });
    </script>
</head>
<body class="min-h-screen bg-fixed bg-cover bg-center login-bg">
    <div id="root" class="min-h-screen grid place-items-center px-4 py-8"></div>

    <script type="text/babel">
        function ForgotCard() {
            const [email, setEmail] = React.useState('');
            const [submitting, setSubmitting] = React.useState(false);
            const [message, setMessage] = React.useState('');
            const [error, setError] = React.useState('');

            const handleSubmit = async (e) => {
                e.preventDefault();
                setSubmitting(true);
                setMessage('');
                setError('');

                try {
                    const formData = new FormData();
                    formData.append('action', 'request_reset');
                    formData.append('email', email);

                    const response = await fetch('auth/reset_password.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        setMessage(data.message);
                        setEmail('');
                    } else {
                        setError(data.message);
                    }
                } catch (err) {
                    setError('An error occurred. Please try again later.');
                    console.error('Error:', err);
                } finally {
                    setSubmitting(false);
                }
            };

            return (
                <div className="relative grid w-full max-w-[1100px] grid-cols-1 md:grid-cols-[1.2fr_1fr] overflow-hidden border border-slate-200 bg-white shadow-xl">
                    <div className="relative hidden md:block bg-center bg-cover left-panel-bg">
                        <div className="absolute inset-x-0 top-0 flex items-center gap-3 bg-black/25 backdrop-blur-[2px] px-6 py-4">
                            <img src="img/evsu_logo.png" alt="EVSU" className="h-10 w-10 object-contain drop-shadow" />
                            <div className="text-white leading-tight drop-shadow">
                                <div className="font-extrabold tracking-wider text-[26px] md:text-[28px]">EVSU-OC | BudgetTrack</div>
                                <div className="mt-1 h-1 bg-[#ffd54a] evsu-underline"></div>
                                <div className="font-semibold tracking-[.35em] text-xs md:text-sm opacity-95">EASTERN VISAYAS STATE UNIVERSITY ORMOC CAMPUS</div>
                            </div>
                        </div>
                        <svg className="absolute -right-px top-0 h-full w-24" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M100,100 L100,0 C108,40 0,65 80,100 L0,100 Z" fill="#ffffff" />
                        </svg>
                    </div>

                    <div className="p-10">
                        <h1 className="mb-6 text-[56px] leading-none text-[#b30000] sign-in-title">FORGOT PASSWORD</h1>
                        <p className="text-slate-500 mb-6">Enter your EVSU email to receive a password reset link.</p>
                        
                        {message && (
                            <div className="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                                {message}
                            </div>
                        )}
                        
                        {error && (
                            <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                                {error}
                            </div>
                        )}
                        
                        <form className="space-y-4" onSubmit={handleSubmit}>
                            <div>
                                <label className="block font-semibold mb-1">Email</label>
                                <input 
                                    type="email" 
                                    name="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="example@evsu.edu.ph" 
                                    className="w-full rounded-none border border-slate-300 px-4 py-3 outline-none focus:border-[#b30000] focus:ring-2 focus:ring-[#b30000]/20" 
                                    required 
                                />
                            </div>
                            <button 
                                type="submit" 
                                disabled={submitting}
                                className="w-full rounded-none bg-[#cc0000] py-3 font-bold text-white hover:bg-[#b30000] disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {submitting ? 'Sending...' : 'Send Reset Link'}
                            </button>
                        </form>
                        <div className="flex items-center justify-between text-sm text-slate-600 mt-3">
                            <a className="text-[#b30000] font-semibold" href="login.php">Back to Login</a>
                        </div>
                        <p className="text-sm text-slate-500 mt-6">© <span id="year"></span> EVSU–Ormoc Campus • BudgetTrack</p>
                        <div className="pt-6">
                            <a href="index.php" className="text-[#b30000] font-semibold hover:underline">← Back to Homepage</a>
                        </div>
                    </div>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<ForgotCard />);
    </script>
    <?php include __DIR__ . '/components/footer.php'; ?>
</body>
</html>

