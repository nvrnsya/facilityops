<?php
include("config.php");

// Fetch active FAQs ordered by display_order
$query = "SELECT * FROM faqs WHERE is_active = 1 ORDER BY display_order ASC";
$result = mysqli_query($connect, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="assets/images/favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ | FacilityOps</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/profile-menu.css">
    <link rel="stylesheet" href="assets/css/FAQpage.css">
    
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .modal-header h3 {
            color: #1a365d;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            color: #718096;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .close-modal:hover {
            background: #f7fafc;
            color: #2d3748;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: #2d3748;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2c5282;
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-submit {
            flex: 1;
            padding: 12px 24px;
            background: linear-gradient(135deg, #2c5282 0%, #1a365d 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #1a365d 0%, #0f2847 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 54, 93, 0.3);
        }

        .btn-cancel {
            padding: 12px 24px;
            background: #e2e8f0;
            color: #2d3748;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #cbd5e0;
        }

        .success-message,
        .error-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 600px) {
            .modal-content {
                padding: 24px 20px;
                width: 95%;
            }

            .modal-header h3 {
                font-size: 20px;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header" id="header">
        <header>
            <a href="landpage.php" class="logo-container">
                <img src="assets/images/favicon.png" alt="Logo" style="width: 32px; height: 32px; margin-right: 8px;">
                <h1>FacilityOps</h1>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Login</a></li>
                    <li>|</li>
                    <li><a href="publicregister.php">Register</a></li>
                </ul>
            </nav>
        </header>
    </div>

    <!-- FAQ SECTION -->
    <section class="faq-section">
        <h2>Frequently Asked Questions</h2>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while($faq = mysqli_fetch_assoc($result)): ?>
                <div class="faq-item">
                    <button class="faq-question"><?php echo htmlspecialchars($faq['question']); ?></button>
                    <div class="faq-answer">
                        <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="faq-item">
                <div class="faq-answer" style="max-height: none; padding: 20px; text-align: center; color: #718096;">
                    <p>No FAQs available at the moment. Please check back later.</p>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- HELP DECK SECTION -->
    <section class="help-deck-section">
        <h2>Need More Help?</h2>
        <p class="help-deck-subtitle">Choose the best way to get in touch with us</p>
        
        <div class="help-deck-container">
            <div class="help-card">
                <div class="help-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <h3>Email Support</h3>
                <p>Get help via email within 24 hours</p>
                <button class="help-btn" onclick="openEmailModal()">Send Email</button>
            </div>

            <div class="help-card">
                <div class="help-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <h3>Phone Support</h3>
                <p>Call us during office hours</p>
                <a href="tel:011-11577404" class="help-btn">+60 11-11577404</a>
            </div>

            <div class="help-card">
                <div class="help-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h3>Documentation</h3>
                <p>Browse our complete guide</p>
                <a href="../assets/documents/FACILTYOPS USER MANUAL.pdf" class="help-btn" target="_blank">View Docs</a>
            </div>
        </div>

        <div class="help-deck-footer">
            <p>Office Hours: 8:00 AM - 5:00 PM (Sunday - Thursday)</p>
            <p>For urgent matters regarding space or reservation, please contact the Unit who owns the space</p>
        </div>
    </section>

    <!-- EMAIL CONTACT MODAL -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Contact Support</h3>
                <button class="close-modal" onclick="closeEmailModal()">&times;</button>
            </div>

            <div id="formMessage"></div>

            <form id="contactForm" method="POST" action="send_support_email.php">
                <div class="form-group">
                    <label for="name">Your Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Your Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a subject...</option>
                        <option value="Booking Inquiry">Booking Inquiry</option>
                        <option value="Technical Support">Technical Support</option>
                        <option value="Cancellation Request">Cancellation Request</option>
                        <option value="Feedback">Feedback</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeEmailModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER - Contact Us Section -->
    <footer class="footer-contact">
        <div class="footer-contact-content">
            <img src="../assets/images/polilogo.png" alt="Poli Logo">
            <p>Km 08, Jalan Paka, 23000 Kuala Dungun, Terengganu</p>
            <p><a href="https://psmza.mypolycc.edu.my/" target="_blank">https://psmza.mypolycc.edu.my/</a></p>
            <p>Tel: 09-8400800</p>
            <p>Fax: 09-8458781</p>
            <p class="disclaimer">Disclaimer</p>
            <p>Office Hours: 8:00 AM - 5:00 PM (Monday - Thursday)</p>
            <p class="notice">For urgent matters regarding space or reservation, please contact the Unit who owns the space</p>
        </div>
        
        <!-- Copyright -->
        <div class="footer-copyright">
            <p>&copy; 2025 FacilityOps | Designed by Team Toman | All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Back to Top Button
        const backToTopBtn = document.createElement('button');
        backToTopBtn.id = 'backToTop';
        backToTopBtn.innerHTML = '↑';
        backToTopBtn.title = 'Back to Top';
        document.body.appendChild(backToTopBtn);

        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // FAQ accordion toggle
        document.querySelectorAll('.faq-question').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.classList.toggle('active');
                const answer = btn.nextElementSibling;
                answer.style.maxHeight = answer.style.maxHeight ? null : answer.scrollHeight + 'px';
            });
        });

        // Modal functions
        function openEmailModal() {
            document.getElementById('emailModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeEmailModal() {
            document.getElementById('emailModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('contactForm').reset();
            document.getElementById('formMessage').innerHTML = '';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('emailModal');
            if (event.target === modal) {
                closeEmailModal();
            }
        }

        // Handle form submission with AJAX
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('formMessage');
            
            fetch('send_support_email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="success-message">' + data.message + '</div>';
                    document.getElementById('contactForm').reset();
                    setTimeout(() => {
                        closeEmailModal();
                    }, 2000);
                } else {
                    messageDiv.innerHTML = '<div class="error-message">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="error-message">An error occurred. Please try again.</div>';
            });
        });
    </script>
</body>
</html>