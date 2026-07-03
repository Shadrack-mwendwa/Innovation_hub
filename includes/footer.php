    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <!-- About Section - Logo Removed -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-about">
                        <h5 class="fw-bold mb-3">Makueni County Innovation Hub</h5>
                        <p class="small opacity-75">Empowering innovators, entrepreneurs, and tech enthusiasts in Makueni County to transform ideas into reality through mentorship, training, and funding opportunities.</p>
                        <div class="social-links mt-3">
                            <a href="#" class="me-2 text-white"><i class="fab fa-facebook-f fa-lg"></i></a>
                            <a href="#" class="me-2 text-white"><i class="fab fa-twitter fa-lg"></i></a>
                            <a href="#" class="me-2 text-white"><i class="fab fa-linkedin-in fa-lg"></i></a>
                            <a href="#" class="me-2 text-white"><i class="fab fa-instagram fa-lg"></i></a>
                            <a href="#" class="me-2 text-white"><i class="fab fa-youtube fa-lg"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>index.php" class="text-white-50 text-decoration-none small">Home</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>feedback.php" class="text-white-50 text-decoration-none small">Feedback</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>contact.php" class="text-white-50 text-decoration-none small">Contact</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>register_class.php" class="text-white-50 text-decoration-none small">Computer Classes</a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>events.php" class="text-white-50 text-decoration-none small">Events</a></li>
                    </ul>
                </div>
                
                <!-- Programs -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3">Our Programs</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none small">Innovation Bootcamp</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none small">Startup Incubation</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none small">Events Program</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none small">classes</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none small">Certification Program</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-md-4">
                    <h6 class="fw-bold">Contact Information</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-warning"></i> P.O. Box 78 – 90-300 Wote, Makueni</li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-warning"></i> 0795717885</li>
                        <li class="mb-2"><i class="fas fa-mobile-alt me-2 text-warning"></i> 0780717885</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2 text-warning"></i> info@makueni.go.ke</li>
                    </ul>
                </div>
            </div>
            
            <!-- Kenyan Flag Stripes in Footer -->
            <div class="footer-flag my-4" style="height: 4px; display: flex; border-radius: 2px; overflow: hidden;">
                <div style="background: #000000; flex: 1;"></div>
                <div style="background: #BB0000; flex: 1;"></div>
                <div style="background: #006600; flex: 1;"></div>
                <div style="background: #FFFFFF; flex: 1;"></div>
            </div>
            
            <!-- Bottom Footer -->
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="small mb-0">&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <p class="small mb-0">Powered by Makueni Innovation Hub</p>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12 text-center">
                        <p class="small text-white-50 mb-0">
                            <a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a> | 
                            <a href="#" class="text-white-50 text-decoration-none">Terms of Service</a> | 
                            <a href="#" class="text-white-50 text-decoration-none">Accessibility</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .footer {
            background: linear-gradient(135deg, #0a3d2a 0%, #1a5c3a 100%);
            color: white;
            padding: 50px 0 20px;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FFD700, #FFA000);
        }
        
        .footer-links a:hover {
            color: #FFD700 !important;
            padding-left: 5px;
            transition: all 0.3s ease;
        }
        
        .footer-contact i {
            width: 25px;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: #FFD700;
            color: #0a3d2a !important;
            transform: translateY(-3px);
        }
        
        .footer-flag {
            opacity: 0.5;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 20px;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>