<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Treis Adiutor - Professional Support Services</title>
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
    <!-- Header -->
    <?php include 'components/navbar.php'; ?>

        <main class="pt-32 pb-20">
            <div class="container mx-auto px-4">
                <h1 class="text-4xl font-bold text-primary mb-4">About Us</h1>
                <h2 class="text-xl font-semibold text-accent mb-6">One Team, One Vision</h2>
                <p class="text-gray-700 mb-6">
                    We are a group of three individuals committed to delivering high-quality and premium services
                    to our clients. We started in 2019 offering OED (Online Education) and mathematics-related services
                    for our clients, and since then, we have expanded our service offerings from written works, editing,
                    digital artworks, and programming. Currently, we have catered to and provided over 2,000 services
                    to our fully satisfied clients.
                </p>
                <h3 class="text-2xl font-bold text-primary mb-4">Our Journey</h3>
                <p class="text-gray-700 mb-6">
                    Treis Adiutor began with a shared passion for knowledge and a drive to assist others in academic
                    and professional development. What started as a small initiative soon grew into a stronger, better,
                    and premium quality commissioner for clients across multiple fields. In our journey, we have
                    collaborated with a wide range of industries, from education to digital marketing, helping
                    organizations and individuals achieve their goals. With a deep commitment to quality and client
                    satisfaction, we’ve built a reputation for offering efficient, customized, and reliable solutions.
                </p>
                <h3 class="text-2xl font-bold text-primary mb-4">Our Philosophy</h3>
                <p class="text-gray-700 mb-6">
                    At Treis Adiutor, we believe that success lies in collaboration, continuous learning, and adapting
                    to new challenges. Our team is committed to embracing innovation and excellence in every service we
                    provide. Whether it's delivering a well-researched paper, designing an impactful logo, or creating
                    a user-friendly website, we pour our expertise and passion into everything we do. We value integrity,
                    creativity, and a relentless pursuit of improvement, which drives our ability to meet our clients'
                    evolving needs.
                </p>
                <h3 class="text-2xl font-bold text-primary mb-4">Why Choose Us?</h3>
                <p class="text-gray-700">
                    When you work with Treis Adiutor, you’re not just hiring a commissioner—you’re partnering with a
                    dedicated team of professionals committed to your success. With our extensive experience, academic
                    excellence, and problem-solving mindset, we offer the highest quality of work with a focus on
                    meeting deadlines and exceeding client expectations. Whether you are a student, entrepreneur,
                    or a large organization, we tailor our solutions to fit your unique needs.
                </p>
            </div>
        </main>

        
    <!-- Footer -->
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