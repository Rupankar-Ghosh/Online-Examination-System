<!doctype html>
<html lang="en"> 
 <head> 
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>ExamFlow - Online Examination System</title> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"> 
  <style>
        :root {
            --primary-color: #4a6bff;
            --secondary-color: #3a5bef;
            --accent-color: #ff6b6b;
            --dark-color: #1a237e;
            --light-color: #f8fafc;
            --text-color: #2d3748;
            --light-text: #718096;
            --success-color: #4caf50;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        /* Premium Preloader */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--light-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }

        .preloader-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin-bottom: 30px;
        }

        .preloader-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 8px solid transparent;
            border-radius: 50%;
            animation: spin 2s linear infinite;
            border-top-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(74, 107, 255, 0.3);
        }

        .preloader-circle:nth-child(1) {
            animation-delay: 0.1s;
            border-top-color: rgba(74, 107, 255, 0.8);
        }

        .preloader-circle:nth-child(2) {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            animation-delay: 0.2s;
            border-top-color: rgba(255, 107, 107, 0.8);
            animation-duration: 1.5s;
        }

        .preloader-circle:nth-child(3) {
            width: 60%;
            height: 60%;
            top: 20%;
            left: 20%;
            animation-delay: 0.3s;
            border-top-color: rgba(58, 91, 239, 0.8);
            animation-duration: 1s;
        }

        .preloader-progress {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .preloader-text {
            font-size: 18px;
            color: var(--primary-color);
            margin-top: 20px;
            text-align: center;
            position: relative;
            height: 30px;
            overflow: hidden;
        }

        .typing-text {
            display: inline-block;
            overflow: hidden;
            white-space: nowrap;
            border-right: 2px solid var(--primary-color);
            animation: typing 3.5s steps(40, end) infinite, blink-caret 0.75s step-end infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: var(--primary-color); }
        }

        /* Full Page Background Animations */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.95) 0%, rgba(248, 250, 252, 0.98) 100%);
            z-index: 1;
        }

        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            filter: blur(20px);
            animation: floatShape 15s infinite linear; /* Changed to avoid conflict */
        }

        .bg-shape:nth-child(1) {
            width: 600px;
            height: 600px;
            top: -200px;
            left: -200px;
            background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
            animation-duration: 20s;
        }

        .bg-shape:nth-child(2) {
            width: 400px;
            height: 400px;
            bottom: -100px;
            right: -100px;
            background: radial-gradient(circle, var(--accent-color) 0%, transparent 70%);
            animation-duration: 25s;
            animation-delay: 2s;
        }

        .bg-shape:nth-child(3) {
            width: 300px;
            height: 300px;
            top: 30%;
            right: 20%;
            background: radial-gradient(circle, var(--secondary-color) 0%, transparent 70%);
            animation-duration: 18s;
            animation-delay: 4s;
        }

        .bg-shape:nth-child(4) {
            width: 500px;
            height: 500px;
            bottom: 10%;
            left: 25%;
            background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
            animation-duration: 22s;
            animation-delay: 1s;
        }

        .bg-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .bg-particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background-color: rgba(74, 107, 255, 0.1);
            border-radius: 50%;
            animation: floatParticle 20s infinite linear; /* Changed to avoid conflict */
        }

        /* Keyframes for floating shapes */
        @keyframes floatShape {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.1;
            }
            50% {
                transform: translateY(-50px) rotate(180deg);
                opacity: 0.15;
            }
            100% {
                transform: translateY(0) rotate(360deg);
                opacity: 0.1;
            }
        }

        /* Keyframes for floating particles (moving upwards and fading) */
        @keyframes floatParticle {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0.1;
            }
            25% {
                transform: translateY(-25%) translateX(10%);
                opacity: 0.2;
            }
            50% {
                transform: translateY(-50%) translateX(-10%);
                opacity: 0.1;
            }
            75% {
                transform: translateY(-75%) translateX(5%);
                opacity: 0.05;
            }
            100% {
                transform: translateY(-100%) translateX(0);
                opacity: 0;
            }
        }


        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light-color);
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
        }

        section {
            padding: 80px 0;
            position: relative;
        }

        h1, h2, h3, h4 {
            font-weight: 600;
            line-height: 1.3;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 15px rgba(74, 107, 255, 0.3);
            z-index: 1;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(74, 107, 255, 0.4);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
            z-index: -1;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-secondary {
            background: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(74, 107, 255, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(74, 107, 255, 0.3);
        }

        .btn-light {
            background: white;
            color: var(--primary-color);
        }

        .btn-light:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-light {
            background: transparent;
            color: white;
            border: 1px solid white;
        }

        .btn-outline-light:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-full {
            width: 100%;
        }

        /* Header */
        header {
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        header.scrolled {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
        }

        .logo span {
            color: var(--primary-color);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0 ;
        }

        .desktop-menu {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .desktop-menu li a {
            transition: var(--transition);
            font-weight: 500;
            position: relative;
        }

        .desktop-menu li a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: var(--transition);
        }

        .desktop-menu li a:hover::after {
            width: 100%;
        }

        .mobile-menu-button {
            display: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--primary-color);
        }

        .mobile-menu {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow);
            padding: 20px;
            transform: translateY(-150%);
            transition: var(--transition);
            z-index: 99;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .mobile-menu.active {
            transform: translateY(0);
        }

        .mobile-menu ul {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .mobile-menu .btn {
            margin-top: 10px;
        }

        /* Hero Section */
        .hero {
            position: relative;
            overflow: hidden;
        }

        .hero .container {
            display: flex;
            align-items: center;
            min-height: calc(100vh - 80px);
        }

        .hero-content {
            flex: 1;
            max-width: 600px;
            padding-right: 40px;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--dark-color);
            line-height: 1.2;
            position: relative;
            display: inline-block;
        }

        .hero h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            color: var(--light-text);
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
        }

        .quick-join-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            max-width: 500px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .quick-join-form:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .quick-join-form h3 {
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .join-mini-form {
            display: flex;
            gap: 10px;
        }

        .join-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .join-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }

        .hero-image-container {
            flex: 1;
            position: relative;
            max-width: 600px;
        }

        .hero-image {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            transform: perspective(1000px) rotateY(-15deg);
            transition: var(--transition);
        }

        .hero-image:hover {
            transform: perspective(1000px) rotateY(-5deg) translateY(-10px);
        }

        /* Section Header */
        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
            position: relative;
        }

        .section-header h2 {
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--dark-color);
            position: relative;
            display: inline-block;
        }

        .section-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .section-header p {
            color: var(--light-text);
            font-size: 18px;
        }

        /* Features Section */
        .features {
            background-color: white;
            position: relative;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            transform: rotate(15deg) scale(1.1);
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: var(--dark-color);
        }

        .feature-card p {
            color: var(--light-text);
        }

        /* How It Works Section */
        .how-it-works {
            position: relative;
        }

        .steps-container {
            display: flex;
            gap: 50px;
            align-items: center;
        }

        .steps {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .step {
            position: relative;
            padding-left: 80px;
        }

        .step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
            transition: var(--transition);
        }

        .step:hover .step-number {
            transform: scale(1.1);
            box-shadow: 0 0 0 5px rgba(74, 107, 255, 0.2);
        }

        .step h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .step p {
            color: var(--light-text);
            margin-bottom: 15px;
        }

        .step-tip {
            background: rgba(74, 107, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            gap: 10px;
            align-items: center;
            transition: var(--transition);
        }

        .step-tip:hover {
            background: rgba(74, 107, 255, 0.2);
        }

        .step-tip i {
            color: var(--primary-color);
        }

        .step-tip span {
            color: var(--light-text);
            font-size: 14px;
        }

        .interface-preview {
            flex: 1;
            position: relative;
        }

        .exam-mockup {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-hover);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .exam-mockup:hover {
            transform: scale(1.02);
        }

        .mockup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .mockup-header h3 {
            color: var(--dark-color);
        }

        .timer {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-color);
            font-weight: 500;
        }

        .progress-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 3px;
        }

        .question-count {
            color: var(--light-text);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .question h4 {
            margin-bottom: 20px;
            color: var(--dark-color);
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
        }

        .option {
            display: flex;
            gap: 10px;
            padding: 12px 15px;
            border-radius: 8px;
            background: #f8fafc;
            cursor: pointer;
            transition: var(--transition);
        }

        .option:hover {
            background: #edf2f7;
        }

        .option.selected {
            background: rgba(74, 107, 255, 0.1);
            border-left: 3px solid var(--primary-color);
        }

        .option-marker {
            font-weight: 500;
            color: var(--primary-color);
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
        }

        .navigation-buttons .btn {
            padding: 10px 20px;
        }

        /* Testimonials Section */
        .testimonials {
            background-color: white;
            position: relative;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stars {
            color: #ffc107;
            margin-bottom: 20px;
        }

        .quote {
            font-style: italic;
            margin-bottom: 20px;
            color: var(--text-color);
            position: relative;
        }

        .quote::before {
            content: '"';
            font-size: 60px;
            color: rgba(74, 107, 255, 0.1);
            position: absolute;
            top: -20px;
            left: -10px;
            z-index: -1;
        }

        .author {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .author-initials {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .author-info h4 {
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .author-info p {
            color: var(--light-text);
            font-size: 14px;
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: center;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            animation: pulse 15s infinite linear;
            z-index: 0;
        }

        @keyframes pulse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .cta .container {
            position: relative;
            z-index: 1;
        }

        .cta h2 {
            font-size: 36px;
            margin-bottom: 15px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .cta p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        /* Waitlist Section */
        .waitlist {
            background-color: white;
            text-align: center;
            position: relative;
        }

        .waitlist-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: var(--shadow);
            transform: translateY(-50px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .waitlist-container:hover {
            transform: translateY(-55px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
        }

        .waitlist h3 {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--dark-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
        }

        .role-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .success-message i {
            font-size: 20px;
        }

        /* Footer */
        footer {
            background: var(--dark-color);
            color: white;
            padding: 60px 0 20px;
            position: relative;
        }

        .footer-content {
            display: flex;
            flex-wrap: wrap;
            gap: 50px;
            margin-bottom: 40px;
        }

        .footer-logo {
            flex: 1;
            min-width: 250px;
        }

        .footer-logo a {
            font-size: 24px;
            font-weight: 700;
            display: block;
            margin-bottom: 15px;
        }

        .footer-logo span {
            color: var(--accent-color);
        }

        .footer-logo p {
            opacity: 0.8;
        }

        .footer-links {
            flex: 2;
            display: flex;
            flex-wrap: wrap;
            gap: 50px;
        }

        .link-column {
            min-width: 150px;
        }

        .link-column h4 {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .link-column ul {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .link-column a {
            opacity: 0.8;
            transition: var(--transition);
            position: relative;
            padding-bottom: 3px;
        }

        .link-column a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--accent-color);
            transition: var(--transition);
        }

        .link-column a:hover {
            opacity: 1;
            color: var(--accent-color);
        }

        .link-column a:hover::after {
            width: 100%;
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom p {
            opacity: 0.8;
            font-size: 14px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--accent-color);
            transform: translateY(-3px);
        }

        /* Floating elements animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .hero .container {
                flex-direction: column;
                padding-top: 40px;
                padding-bottom: 40px;
            }

            .hero-content {
                padding-right: 0;
                margin-bottom: 40px;
                text-align: center;
            }

            .hero h1::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-image-container {
                max-width: 100%;
            }

            .steps-container {
                flex-direction: column;
            }

            .interface-preview {
                width: 100%;
                max-width: 600px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .desktop-menu {
                display: none;
            }

            .mobile-menu-button {
                display: block;
            }

            .hero h1 {
                font-size: 36px;
            }

            .section-header h2 {
                font-size: 30px;
            }

            .cta h2 {
                font-size: 30px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .footer-content {
                flex-direction: column;
                gap: 30px;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 32px;
            }

            .section-header h2 {
                font-size: 28px;
            }

            .feature-card, .testimonial-card {
                padding: 20px;
            }

            .waitlist-container {
                padding: 30px 20px;
            }

            .role-options {
                flex-direction: column;
                gap: 10px;
            }

            .join-mini-form {
                flex-direction: column;
            }
        }
    </style> 
 </head> 
 <body> 
  <!-- Full Page Background Animation -->
  <div class="bg-animation">
   <div class="bg-overlay"></div>
   <div class="bg-shapes">
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
   </div>
   <div class="bg-particles" id="bgParticles"></div>
  </div> 
  
  <!-- Premium Preloader -->
  <div id="preloader">
   <div class="preloader-container">
    <div class="preloader-circle"></div>
    <div class="preloader-circle"></div>
    <div class="preloader-circle"></div>
    <div class="preloader-progress" id="preloaderProgress">0%</div>
   </div>
   <div class="preloader-text">
    <div class="typing-text">Loading ExamFlow...</div>
   </div>
  </div> 

  <!-- Header --> 
  <header id="header"> 
   <div class="container"> 
    <nav> 
     <div class="logo"> <a href="index.php">Exam<span>Flow</span></a> 
     </div> 
     <ul class="desktop-menu"> 
      <li><a href="#features">Features</a></li> 
      <li><a href="#how-it-works">How It Works</a></li> 
      <li><a href="#testimonials">Testimonials</a></li> 
      <li><a href="auth\login.html" class="btn btn-outline">Log In</a></li> 
      <li><a href="auth\signup.html" class="btn btn-primary">Sign Up</a></li> 
     </ul> 
     <div class="mobile-menu-button"> <i class="fas fa-bars"></i> 
     </div> 
    </nav> 
    <div class="mobile-menu"> 
     <ul> 
      <li><a href="#features">Features</a></li> 
      <li><a href="#how-it-works">How It Works</a></li> 
      <li><a href="#testimonials">Testimonials</a></li> 
      <li><a href="take_exam.php" class="btn btn-primary btn-full">Join an Exam</a></li> 
      <li><a href="auth\login.html" class="btn btn-outline btn-full">Log In / Sign Up</a></li> 
     </ul> 
    </div> 
   </div> 
  </header> 

  <!-- Hero Section --> 
  <section class="hero"> 
   <div class="container"> 
    <div class="hero-content"> 
     <h1>Simplify Online Assessments</h1> 
     <p>Create, manage, and take exams with ease. Open to anyone, anywhere.</p> 
     <div class="hero-buttons"> <a href="take_exam.php" class="btn btn-primary floating" style="animation-delay: 0.2s;">Join an Exam</a> <a href="create_exam.php" class="btn btn-secondary floating" style="animation-delay: 0.4s;">Create an Exam</a> 
     </div> 
     <!-- Quick Exam Join Form --> 
     <div class="quick-join-form floating" style="animation-delay: 0.6s;"> 
      <h3>Have an exam ID?</h3> 
      <form action="take_exam.php" method="get" class="join-mini-form"> 
       <input type="text" name="exam-id" placeholder="Enter Exam ID" class="join-input"> <button type="submit" class="btn btn-primary">Go</button> 
      </form> 
     </div> 
    </div> 
    <div class="hero-image-container"> 
     <img src="img/2.png" alt="ExamFlow Dashboard Preview" class="hero-image floating" style="animation-delay: 0.8s;"> 
    </div> 
   </div> 
  </section> 

  <!-- Features Section --> 
  <section id="features" class="features"> 
   <div class="container"> 
    <div class="section-header"> 
     <h2>Designed for modern education</h2> 
     <p>ExamFlow combines powerful features with an easy-to-use interface to create the ideal examination experience.</p> 
    </div> 
    <div class="features-grid"> 
     <div class="feature-card floating" style="animation-delay: 0.2s;"> 
      <div class="feature-icon"> <i class="fas fa-pen"></i> 
      </div> 
      <h3>Exam Creation</h3> 
      <p>Intuitive interface for creating various question types with support for rich media attachments.</p> 
     </div> 
     <div class="feature-card floating" style="animation-delay: 0.4s;"> 
      <div class="feature-icon"> <i class="fas fa-clock"></i> 
      </div> 
      <h3>Timed Assessments</h3> 
      <p>Set time limits for exams with automatic submission when time expires. Students see real-time countdown.</p> 
     </div> 
     <div class="feature-card floating" style="animation-delay: 0.6s;"> 
      <div class="feature-icon"> <i class="fas fa-robot"></i> 
      </div> 
      <h3>Auto-Grading</h3> 
      <p>Automatically grade objective questions while providing tools for efficient grading of subjective responses.</p> 
     </div> 
     <div class="feature-card floating" style="animation-delay: 0.8s;"> 
      <div class="feature-icon"> <i class="fas fa-lock"></i> 
      </div> 
      <h3>Secure Environment</h3> 
      <p>Prevent cheating with features like randomized questions, browser lockdown, and copy-paste restrictions.</p> 
     </div> 
     <div class="feature-card floating" style="animation-delay: 1.0s;"> 
      <div class="feature-icon"> <i class="fas fa-chart-bar"></i> 
      </div> 
      <h3>Performance Analytics</h3> 
      <p>Comprehensive dashboards showing individual and class performance metrics with exportable reports.</p> 
     </div> 
     <div class="feature-card floating" style="animation-delay: 1.2s;"> 
      <div class="feature-icon"> <i class="fas fa-mobile-alt"></i> 
      </div> 
      <h3>Responsive Design</h3> 
      <p>Take exams on any device with a fully responsive interface that works on desktops, tablets, and smartphones.</p> 
     </div> 
    </div> 
   </div> 
  </section> 

  <!-- How It Works Section --> 
  <section id="how-it-works" class="how-it-works"> 
   <div class="container"> 
    <div class="section-header"> 
     <h2>How ExamFlow works</h2> 
     <p>A simple workflow that makes exam creation, administration, and grading effortless.</p> 
    </div> 
    <div class="steps-container"> 
     <div class="steps"> 
      <div class="step floating" style="animation-delay: 0.2s;"> 
       <div class="step-number">
        1
       </div> 
       <h3>Create your exam</h3> 
       <p>Build your exam with multiple question types, set time limits, and customize settings.</p> 
       <div class="step-tip"> <i class="fas fa-lightbulb"></i> <span>Add images, videos, and equations to make your questions more engaging.</span> 
       </div> 
      </div> 
      <div class="step floating" style="animation-delay: 0.4s;"> 
       <div class="step-number">
        2
       </div> 
       <h3>Share with students</h3> 
       <p>Send secure links or access codes to your students for scheduled or on-demand exams.</p> 
       <div class="step-tip"> <i class="fas fa-lightbulb"></i> <span>Schedule exams in advance with automatic activation and deactivation.</span> 
       </div> 
      </div> 
      <div class="step floating" style="animation-delay: 0.6s;"> 
       <div class="step-number">
        3
       </div> 
       <h3>Analyze results</h3> 
       <p>Review automatically graded responses, provide feedback, and generate detailed reports.</p> 
       <div class="step-tip"> <i class="fas fa-lightbulb"></i> <span>Identify knowledge gaps with question-by-question performance analytics.</span> 
       </div> 
      </div> 
     </div> 
     <div class="interface-preview"> 
      <div class="exam-mockup floating" style="animation-delay: 0.2s;"> 
       <div class="mockup-header"> 
        <h3>Biology Final Exam</h3> 
        <div class="timer">
         <i class="fas fa-clock"></i> 45:23 remaining
        </div> 
       </div> 
       <div class="progress-bar"> 
        <div class="progress" style="width: 20%;"></div> 
       </div> 
       <div class="question-count">
        Question 3 of 15
       </div> 
       <div class="question"> 
        <h4>Which of the following is NOT a function of the cell membrane?</h4> 
        <div class="options"> 
         <div class="option"> <span class="option-marker">A.</span> <span>Regulating what enters and exits the cell</span> 
         </div> 
         <div class="option"> <span class="option-marker">B.</span> <span>Providing structural support to the cell</span> 
         </div> 
         <div class="option selected"> <span class="option-marker">C.</span> <span>Producing energy through cellular respiration</span> 
         </div> 
         <div class="option"> <span class="option-marker">D.</span> <span>Recognizing other cells</span> 
         </div> 
        </div> 
       </div> 
       <div class="navigation-buttons"> <button class="btn btn-outline"><i class="fas fa-arrow-left"></i> Previous</button> <button class="btn btn-primary">Next <i class="fas fa-arrow-right"></i></button> 
       </div> 
      </div> 
     </div> 
    </div> 
   </div> 
  </section> 

  <!-- Testimonials Section --> 
  <section id="testimonials" class="testimonials"> 
   <div class="container"> 
    <div class="section-header"> 
     <h2>Trusted by educators</h2> 
     <p>See what teachers and students are saying about their experience with ExamFlow.</p> 
    </div> 
    <div class="testimonials-grid"> 
     <div class="testimonial-card floating" style="animation-delay: 0.2s;"> 
      <div class="stars"> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> 
      </div> 
      <p class="quote">"ExamFlow has transformed how I administer tests. The auto-grading feature alone saves me hours every week, allowing me to focus more on teaching."</p> 
      <div class="author"> 
       <div class="author-initials">
        RM
       </div> 
       <div class="author-info"> 
        <h4>Dr. Rebecca Martinez</h4> 
        <p>Biology Professor, Stanford University</p> 
       </div> 
      </div> 
     </div> 
     <div class="testimonial-card floating" style="animation-delay: 0.4s;"> 
      <div class="stars"> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> 
      </div> 
      <p class="quote">"The analytics provided by ExamFlow have helped me identify which concepts my students struggle with, allowing me to adjust my teaching accordingly."</p> 
      <div class="author"> 
       <div class="author-initials">
        MJ
       </div> 
       <div class="author-info"> 
        <h4>Michael Johnson</h4> 
        <p>High School Math Teacher</p> 
       </div> 
      </div> 
     </div> 
     <div class="testimonial-card floating" style="animation-delay: 0.6s;"> 
      <div class="stars"> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star"></i> <i class="fas fa-star-half-alt"></i> 
      </div> 
      <p class="quote">"As a student, I appreciate the clear interface and instant feedback after exams. The ability to review my mistakes helps me learn from them."</p> 
      <div class="author"> 
       <div class="author-initials">
        AS
       </div> 
       <div class="author-info"> 
        <h4>Aisha Sayed</h4> 
        <p>Engineering Student</p> 
       </div> 
      </div> 
     </div> 
    </div> 
   </div> 
  </section> 

  <!-- CTA Section --> 
  <section class="cta"> 
   <div class="container"> 
    <h2>Ready to transform your examination process?</h2> 
    <p>Join thousands of educators who are saving time and improving assessment quality with ExamFlow.</p> 
    <div class="cta-buttons"> <a href="#waitlist" class="btn btn-light floating" style="animation-delay: 0.2s;">Join Waitlist</a> <a href="create_exam.php" class="btn btn-outline-light floating" style="animation-delay: 0.4s;">Request Demo</a> 
    </div> 
   </div> 
  </section> 

  <!-- Waitlist Section --> 
  <section id="waitlist" class="waitlist"> 
   <div class="container"> 
    <div class="waitlist-container floating" style="animation-delay: 0.2s;"> 
     <h3>Join our waitlist for early access</h3> 
     <form id="waitlistForm" class="waitlist-form"> 
      <div class="form-group"> 
       <input type="email" id="email" placeholder="Your email address" required> 
      </div> 
      <div class="role-options"> 
       <div class="checkbox-group"> 
        <input type="checkbox" id="teacher-role" name="role"> <label for="teacher-role">I'm a teacher</label> 
       </div> 
       <div class="checkbox-group"> 
        <input type="checkbox" id="student-role" name="role"> <label for="student-role">I'm a student</label> 
       </div> 
      </div> <button type="submit" class="btn btn-primary btn-full">Join Waitlist</button> 
     </form> 
     <div id="success-message" class="success-message" style="display: none;"> <i class="fas fa-check-circle"></i> <span>Thanks for joining! We'll notify you when you're granted access.</span> 
     </div> 
    </div> 
   </div> 
  </section> 

  <!-- Footer --> 
  <footer> 
   <div class="container"> 
    <div class="footer-content"> 
     <div class="footer-logo"> <a href="index.php">Exam<span>Flow</span></a> 
      <p>The modern exam management solution</p> 
     </div> 
     <div class="footer-links"> 
      <div class="link-column"> 
       <h4>Product</h4> 
       <ul> 
        <li><a href="#features">Features</a></li> 
        <li><a href="#how-it-works">How It Works</a></li> 
        <li><a href="#testimonials">Testimonials</a></li> 
        <li><a href="#waitlist">Join Waitlist</a></li> 
       </ul> 
      </div> 
      <div class="link-column"> 
       <h4>Resources</h4> 
       <ul> 
        <li><a href="#">Blog</a></li> 
        <li><a href="#">Help Center</a></li> 
        <li><a href="#">Tutorials</a></li> 
        <li><a href="#">FAQ</a></li> 
       </ul> 
      </div> 
      <div class="link-column"> 
       <h4>Company</h4> 
       <ul> 
        <li><a href="#">About Us</a></li> 
        <li><a href="#">Contact</a></li> 
        <li><a href="#">Privacy Policy</a></li> 
        <li><a href="#">Terms of Service</a></li> 
       </ul> 
      </div> 
     </div> 
    </div> 
    <div class="footer-bottom"> 
     <p>© 2025 ExamFlow. All rights reserved.</p> 
     <div class="social-links"> <a href="#"><i class="fab fa-twitter"></i></a> <a href="#"><i class="fab fa-facebook"></i></a> <a href="#"><i class="fab fa-linkedin"></i></a> <a href="#"><i class="fab fa-instagram"></i></a> 
     </div> 
    </div> 
   </div> 
  </footer> 
  
  <script>
        // Premium Preloader with Progress Animation
        let progress = 0;
        const preloaderProgress = document.getElementById('preloaderProgress');
        const preloader = document.getElementById('preloader');
        
        const progressInterval = setInterval(() => {
            progress += Math.floor(Math.random() * 5) + 1;
            if (progress > 100) progress = 100;
            preloaderProgress.textContent = progress + '%';
            
            if (progress === 100) {
                clearInterval(progressInterval);
                setTimeout(() => {
                    preloader.style.opacity = '0';
                    preloader.style.visibility = 'hidden';
                }, 500);
            }
        }, 50);

        // Create background particles
        function createParticles() {
            const container = document.getElementById('bgParticles');
            // Clear existing particles to prevent duplicates on re-initialization
            container.innerHTML = ''; 
            const particleCount = Math.floor(window.innerWidth / 15); // Adjusted particle count
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('bg-particle');
                
                // Random position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                
                // Random size
                const size = Math.random() * 3 + 1;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random animation duration
                particle.style.animationDuration = Math.random() * 20 + 10 + 's';
                particle.style.animationDelay = Math.random() * 5 + 's';
                
                // Random opacity
                particle.style.opacity = Math.random() * 0.5 + 0.1;
                
                container.appendChild(particle);
            }
        }

        // Initialize background particles on load and resize
        window.addEventListener('load', createParticles);
        window.addEventListener('resize', createParticles); // Re-create particles on resize

        // Mobile Menu Toggle
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('active');
        });

        // Close mobile menu when clicking a link
        document.querySelectorAll('.mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelector('.mobile-menu').classList.remove('active');
            });
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.getElementById('header').classList.add('scrolled');
            } else {
                document.getElementById('header').classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Waitlist form submission
        document.getElementById('waitlistForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simulate form submission
            document.getElementById('success-message').style.display = 'flex';
            this.reset();
            
            // Hide success message after 5 seconds
            setTimeout(function() {
                document.getElementById('success-message').style.display = 'none';
            }, 5000);
        });

        // Animation on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.feature-card, .testimonial-card, .step, .exam-mockup, .waitlist-container');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (elementPosition < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        }

        // Set initial state for animated elements
        document.querySelectorAll('.feature-card, .testimonial-card, .step, .exam-mockup, .waitlist-container').forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });

        // Run animation check on load and scroll
        window.addEventListener('load', animateOnScroll);
        window.addEventListener('scroll', animateOnScroll);
    </script>  
 </body>
</html>
