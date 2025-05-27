<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treis Adiutor - Professional Support Services</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3a5a78',
                        secondary: '#6c9ab5',
                        accent: '#f9a826',
                        dark: '#1a2a3a',
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans text-gray-800">
    <?php include 'components/navbar.php'; ?>


    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-primary to-secondary text-white pt-32 pb-20">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-10 md:mb-0">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">Professional Support When You Need It Most</h1>
                    <p class="text-lg md:text-xl opacity-90 mb-8">Treis Adiutor, your trusted academic helper. Fast, reliable, and premium quality work for whatever you need.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#services" class="bg-accent hover:bg-yellow-500 text-dark font-semibold px-6 py-3 rounded-full transition-colors duration-300">
                            View Services
                        </a>
                        <a href="get-started.php" class="bg-transparent border-2 border-white hover:bg-white hover:text-primary font-semibold px-6 py-3 rounded-full transition-colors duration-300">
                            Get Started
                        </a>
                    </div>
                </div>
                <div class="md:w-1/2 flex justify-center">
                    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
                    <lottie-player 
                        src="https://assets2.lottiefiles.com/packages/lf20_x17ybolp.json"
                        background="transparent"
                        speed="1"
                        style="width: 100%; height: 400px;"
                        loop
                        autoplay>
                    </lottie-player>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-primary mb-4">Why Choose Treis Adiutor</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">We combine expertise, dedication, and innovation to deliver exceptional results and support for our clients.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white p-8 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="text-accent text-4xl mb-4">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Expert Guidance</h3>
                    <p class="text-gray-600">Access to industry professionals with years of experience and specialized knowledge.</p>
                </div>
                
                <!-- Feature 2 -->
                <div class="bg-white p-8 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="text-accent text-4xl mb-4">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Quick Response</h3>
                    <p class="text-gray-600">Fast turnaround times and prompt communication to address your needs efficiently.</p>
                </div>
                
                <!-- Feature 3 -->
                <div class="bg-white p-8 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                    <div class="text-accent text-4xl mb-4">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Tailored Solutions</h3>
                    <p class="text-gray-600">Customized approaches designed specifically for your unique challenges and goals.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-20">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-primary mb-4">Our Services</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Comprehensive professional support services to help you succeed.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Service 1 -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="h-48 bg-primary flex items-center justify-center">
                        <i class="fas fa-hands-helping text-white text-5xl"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-3">Consultation Services</h3>
                        <p class="text-gray-600 mb-4">Need help organizing your ideas, refining your arguments, or understanding citation formats? We’ll guide you through the writing process so you can confidently craft clear, well-structured, and compelling academic work.</p>
                        <a href="get-started.php" class="text-accent font-semibold hover:underline">Get started →</a>
                    </div>
                </div>
                
                <!-- Service 2 -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="h-48 bg-secondary flex items-center justify-center">
                        <i class="fas fa-tasks text-white text-5xl"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-3">Project Development</h3>
                        <p class="text-gray-600 mb-4">Whether you're creating a personal website, app, or full-stack system, we can help you from planning and best practices to debugging and deployment tips so you can bring your vision to life.</p>
                        <a href="get-started.php" class="text-accent font-semibold hover:underline">Get started →</a>
                    </div>
                </div>
                
                <!-- Service 3 -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="h-48 bg-secondary flex items-center justify-center">
                        <i class="fas fa-chart-line text-white text-5xl"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-3">Creative Design</h3>
                        <p class="text-gray-600 mb-4">Want to improve your poster, brochure, or presentation design? We offer feedback and creative input to help you enhance your visual storytelling, whether you're working on school projects, branding kits, or digital portfolios.</p>
                        <a href="get-started.php" class="text-accent font-semibold hover:underline">Get started →</a>
                    </div>
                </div>
                
                <!-- Service 4 -->
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="h-48 bg-primary flex items-center justify-center">
                        <i class="fas fa-users text-white text-5xl"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-3">Programming Help</h3>
                        <p class="text-gray-600 mb-4">From writing pseudocode and understanding algorithms to resolving bugs and setting up automation scripts, we’re here to walk you through the problem, solving process, so you not only finish the task, but learn along the way.</p>
                        <a href="get-started.php" class="text-accent font-semibold hover:underline">Get started →</a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <a href="get-started.php" class="bg-accent hover:bg-yellow-500 text-dark font-semibold px-8 py-3 rounded-full transition-colors duration-300 inline-block">
                    Request a Service
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-primary mb-4">What Our Clients Say</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Hear from our satisfied clients about their experiences with Treis Adiutor.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                // Database connection
                include 'includes/db.php';
                $query = "SELECT f.id, f.rating, f.comment, u.fullName
                          FROM feedbacks f
                          JOIN users u ON u.userID = f.giver_id
                          JOIN featuredFeedback fFB ON f.id = fFB.feedbackId
                          WHERE fFB.isActive = 1";
                $result = mysqli_query($conn, $query);

                while($row = mysqli_fetch_assoc($result)):
                ?>
                <div class="bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-500 transform hover:-translate-y-2 border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h4 class="font-bold text-primary text-lg tracking-tight">
                                <?php echo htmlspecialchars($row['fullName']); ?>
                            </h4>
                            <div class="flex items-center space-x-1 mt-2">
                                <?php for($i=0; $i < $row['rating']; $i++): ?>
                                    <i class="fas fa-star text-accent"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="text-4xl text-primary/10">
                            <i class="fas fa-quote-right"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 leading-relaxed">
                        <?php echo htmlspecialchars($row['comment']); ?>
                    </p>
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <span class="inline-flex items-center text-xs text-primary/60">
                            <i class="fas fa-check-circle mr-2"></i>
                            Verified Client
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary py-16 text-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="mb-8 md:mb-0 md:w-2/3">
                    <h2 class="text-3xl font-bold mb-4">Ready to get started?</h2>
                    <p class="text-lg opacity-90">Contact us today to discuss how Treis Adiutor can help you achieve your goals.</p>
                </div>
                <div>
                    <a href="get-started.php" class="bg-white text-primary hover:bg-gray-100 font-semibold px-8 py-3 rounded-full transition-colors duration-300 inline-block">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>

    <!-- JavaScript for mobile menu toggle -->
    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>