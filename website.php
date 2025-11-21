<?php
  // This is the public website - no authentication required
  // Only include connection for potential database queries (optional)
  // require "connection.php"; // Commented out as not needed for public site
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="./images/Capture.JPG" type="image/x-icon">
  <script src="https://kit.fontawesome.com/14ff3ea278.js" crossorigin="anonymous"></script>
  <title>BAFRACOO - Fire Safety Equipment</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap');
    
    /* CSS Variables */
    :root {
      --primary-color: #2563eb;
      --primary-dark: #1e40af;
      --secondary-color: #dc2626;
      --accent-color: #059669;
      --warning-color: #f59e0b;
      --white: #ffffff;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-600: #4b5563;
      --gray-800: #1f2937;
      --gray-900: #111827;
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
      --spacing-xs: 0.25rem;
      --spacing-sm: 0.5rem;
      --spacing-md: 1rem;
      --spacing-lg: 1.5rem;
      --spacing-xl: 2rem;
      --spacing-2xl: 3rem;
      --radius-md: 0.5rem;
      --radius-lg: 0.75rem;
      --radius-xl: 1rem;
      --radius-full: 9999px;
      --transition: all 0.3s ease-in-out;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      line-height: 1.6;
      color: var(--gray-800);
      overflow-x: hidden;
    }
    
    /* Header */
    .header {
      background: linear-gradient(135deg, var(--white) 0%, var(--gray-50) 100%);
      border-bottom: 1px solid var(--gray-200);
      position: sticky;
      top: 0;
      z-index: 1000;
      backdrop-filter: blur(10px);
      box-shadow: var(--shadow-lg);
    }
    
    .header-top {
      background: var(--gray-800);
      color: var(--white);
      padding: var(--spacing-sm) 0;
    }
    
    .header-top .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.875rem;
    }
    
    .header-main {
      padding: var(--spacing-lg) 0;
    }
    
    .header-main .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: var(--spacing-xl);
    }
    
    .logo {
      display: flex;
      align-items: center;
      gap: var(--spacing-md);
      text-decoration: none;
      color: var(--gray-800);
    }
    
    .logo img {
      width: 60px;
      height: 60px;
      border-radius: var(--radius-lg);
      object-fit: cover;
    }
    
    .logo-text {
      font-family: 'Poppins', sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--primary-color);
    }
    
    .search-bar {
      flex: 1;
      max-width: 500px;
      position: relative;
    }
    
    .search-form {
      display: flex;
      align-items: center;
      background: var(--white);
      border: 2px solid var(--gray-200);
      border-radius: var(--radius-full);
      overflow: hidden;
      transition: var(--transition);
    }
    
    .search-form:focus-within {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .search-input {
      flex: 1;
      padding: var(--spacing-md) var(--spacing-lg);
      border: none;
      outline: none;
      font-size: 1rem;
    }
    
    .search-btn {
      padding: var(--spacing-md) var(--spacing-xl);
      background: var(--primary-color);
      color: var(--white);
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .search-btn:hover {
      background: var(--primary-dark);
    }
    
    .header-actions {
      display: flex;
      align-items: center;
      gap: var(--spacing-lg);
    }
    
    .support-info {
      text-align: right;
    }
    
    .support-title {
      font-size: 1.125rem;
      font-weight: 700;
      color: var(--gray-800);
      margin-bottom: var(--spacing-xs);
    }
    
    .support-number {
      font-size: 1rem;
      font-weight: 600;
      color: var(--primary-color);
    }
    
    /* Navigation */
    .nav {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
      color: var(--white);
    }
    
    .nav .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
      display: flex;
      justify-content: center;
    }
    
    .nav-menu {
      display: flex;
      list-style: none;
    }
    
    .nav-item {
      position: relative;
    }
    
    .nav-link {
      display: block;
      padding: var(--spacing-lg) var(--spacing-xl);
      color: var(--white);
      text-decoration: none;
      font-weight: 500;
      font-size: 1rem;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }
    
    .nav-link::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 3px;
      background: var(--warning-color);
      transition: width 0.3s ease;
    }
    
    .nav-link:hover::before {
      width: 100%;
    }
    
    .nav-link:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-2px);
    }
    
    /* Hero Section */
    .hero {
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.9), rgba(220, 38, 38, 0.8)), url('./images/fire-safety-bg.jpg') center/cover;
      min-height: 80vh;
      display: flex;
      align-items: center;
      color: var(--white);
      position: relative;
      overflow: hidden;
    }
    
    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.2);
    }
    
    .hero .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
      position: relative;
      z-index: 2;
    }
    
    .hero-content {
      max-width: 600px;
    }
    
    .hero-badge {
      display: inline-block;
      background: var(--warning-color);
      color: var(--white);
      padding: var(--spacing-sm) var(--spacing-lg);
      border-radius: var(--radius-full);
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: var(--spacing-lg);
      animation: pulse 2s infinite;
    }
    
    .hero-title {
      font-family: 'Poppins', sans-serif;
      font-size: clamp(2.5rem, 6vw, 4rem);
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: var(--spacing-lg);
      background: linear-gradient(135deg, var(--white), #e5e7eb);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: slideInUp 1s ease-out;
    }
    
    .hero-subtitle {
      font-size: 1.25rem;
      margin-bottom: var(--spacing-sm);
      opacity: 0.9;
      animation: slideInUp 1s ease-out 0.2s both;
    }
    
    .hero-description {
      font-size: 1.125rem;
      margin-bottom: var(--spacing-2xl);
      opacity: 0.8;
      animation: slideInUp 1s ease-out 0.4s both;
    }
    
    .cta-buttons {
      display: flex;
      gap: var(--spacing-lg);
      flex-wrap: wrap;
      animation: slideInUp 1s ease-out 0.6s both;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--warning-color), #d97706);
      color: var(--white);
      padding: var(--spacing-lg) var(--spacing-2xl);
      border: none;
      border-radius: var(--radius-full);
      font-size: 1.125rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: var(--spacing-sm);
      transition: var(--transition);
      cursor: pointer;
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, #d97706, #b45309);
      transform: translateY(-2px);
      box-shadow: var(--shadow-xl);
    }
    
    .btn-secondary {
      background: rgba(255, 255, 255, 0.2);
      color: var(--white);
      padding: var(--spacing-lg) var(--spacing-2xl);
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: var(--radius-full);
      font-size: 1.125rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: var(--spacing-sm);
      transition: var(--transition);
      cursor: pointer;
      backdrop-filter: blur(10px);
    }
    
    .btn-secondary:hover {
      background: rgba(255, 255, 255, 0.3);
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-2px);
    }
    
    /* Mission Section */
    .mission {
      padding: 100px 0;
      background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
    }
    
    .mission .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }
    
    .mission-intro {
      text-align: center;
      max-width: 800px;
      margin: 0 auto var(--spacing-2xl);
    }
    
    .mission-badge {
      background: var(--primary-color);
      color: var(--white);
      padding: var(--spacing-sm) var(--spacing-lg);
      border-radius: var(--radius-full);
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin-bottom: var(--spacing-lg);
    }
    
    .mission-title {
      font-family: 'Poppins', sans-serif;
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 700;
      color: var(--gray-800);
      margin-bottom: var(--spacing-lg);
    }
    
    .mission-description {
      font-size: 1.25rem;
      color: var(--gray-600);
      line-height: 1.8;
    }
    
    .mission-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: var(--spacing-2xl);
      margin-top: 80px;
    }
    
    .mission-card {
      background: var(--white);
      padding: var(--spacing-2xl);
      border-radius: var(--radius-xl);
      text-align: center;
      box-shadow: var(--shadow-lg);
      transition: var(--transition);
      border: 1px solid var(--gray-200);
    }
    
    .mission-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-xl);
    }
    
    .mission-card-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto var(--spacing-lg);
      font-size: 2rem;
      color: var(--white);
    }
    
    .mission-card h3 {
      font-family: 'Poppins', sans-serif;
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--gray-800);
      margin-bottom: var(--spacing-md);
    }
    
    .mission-card p {
      color: var(--gray-600);
      line-height: 1.7;
    }
    
    /* Animations */
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    /* Categories Section */
    .categories-section {
      padding: var(--spacing-2xl) 0;
      background: var(--white);
    }
    
    .categories-section .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }
    
    .section-title {
      font-family: 'Poppins', sans-serif;
      font-size: clamp(1.75rem, 4vw, 2.5rem);
      font-weight: 700;
      color: var(--gray-800);
      text-align: center;
      margin-bottom: var(--spacing-2xl);
      position: relative;
      padding-bottom: var(--spacing-lg);
    }
    
    .section-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border-radius: var(--radius-full);
    }
    
    .categories {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: var(--spacing-xl);
    }
    
    .category-card {
      background: var(--white);
      border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg);
      overflow: hidden;
      transition: var(--transition);
      box-shadow: var(--shadow-lg);
    }
    
    .category-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-xl);
      border-color: var(--primary-color);
    }
    
    .category-image {
      width: 100%;
      height: 200px;
      overflow: hidden;
      background: var(--gray-100);
    }
    
    .category-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: var(--transition);
    }
    
    .category-card:hover .category-image img {
      transform: scale(1.05);
    }
    
    .category-content {
      padding: var(--spacing-lg);
    }
    
    .category-content h3 {
      font-family: 'Poppins', sans-serif;
      font-size: 1.125rem;
      font-weight: 600;
      color: var(--gray-800);
      margin-bottom: var(--spacing-md);
    }
    
    .category-content ul {
      list-style: none;
      padding: 0;
    }
    
    .category-content li {
      color: var(--gray-600);
      padding: var(--spacing-xs) 0;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: var(--spacing-sm);
    }
    
    .category-content li::before {
      content: '✓';
      color: var(--accent-color);
      font-weight: 600;
    }
    
    /* Featured Section */
    .featured-section {
      padding: var(--spacing-2xl) 0;
      background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
    }
    
    .featured-section .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }
    
    .featured-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: var(--spacing-xl);
    }
    
    .featured-card {
      min-height: 250px;
      border-radius: var(--radius-xl);
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      padding: var(--spacing-2xl);
      position: relative;
      overflow: hidden;
      transition: var(--transition);
    }
    
    .featured-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.3);
      transition: var(--transition);
    }
    
    .featured-card:hover::before {
      background: rgba(0, 0, 0, 0.5);
    }
    
    .featured-card-1 {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .featured-card-2 {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .featured-content {
      position: relative;
      z-index: 2;
      color: var(--white);
    }
    
    .featured-content h3 {
      font-family: 'Poppins', sans-serif;
      font-size: clamp(1.25rem, 3vw, 1.75rem);
      font-weight: 700;
      margin-bottom: var(--spacing-sm);
      line-height: 1.3;
    }
    
    .featured-content p {
      font-size: 1rem;
      margin-bottom: var(--spacing-lg);
      opacity: 0.9;
    }
    
    .featured-content .btn-primary {
      background: var(--white);
      color: var(--gray-800);
      font-size: 0.875rem;
    }
    
    .featured-content .btn-primary:hover {
      background: var(--gray-100);
    }
    
    /* Services Section */
    .services-section {
      padding: var(--spacing-2xl) 0;
      background: var(--white);
    }
    
    .services-section .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }
    
    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: var(--spacing-xl);
    }
    
    .service-card {
      background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
      padding: var(--spacing-2xl);
      border-radius: var(--radius-lg);
      border: 1px solid var(--gray-200);
      text-align: center;
      transition: var(--transition);
    }
    
    .service-card:hover {
      transform: translateY(-8px);
      border-color: var(--primary-color);
      box-shadow: var(--shadow-lg);
    }
    
    .service-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto var(--spacing-lg);
      font-size: 2rem;
      color: var(--white);
    }
    
    .service-card h3 {
      font-family: 'Poppins', sans-serif;
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--gray-800);
      margin-bottom: var(--spacing-md);
    }
    
    .service-card p {
      color: var(--gray-600);
      line-height: 1.6;
    }
    
    /* CTA Section */
    .cta-section {
      padding: var(--spacing-2xl) 0;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    }
    
    .cta-section .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }
    
    .cta-content {
      text-align: center;
      color: var(--white);
    }
    
    .cta-content h2 {
      font-family: 'Poppins', sans-serif;
      font-size: clamp(1.75rem, 4vw, 2.5rem);
      font-weight: 700;
      margin-bottom: var(--spacing-md);
    }
    
    .cta-content p {
      font-size: 1.125rem;
      margin-bottom: var(--spacing-lg);
      opacity: 0.9;
    }
    
    /* Partners Section */
    .partners-section {
      padding: var(--spacing-2xl) 0;
      background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
    }
    
    .partners-section .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }
    
    .partners-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: var(--spacing-xl);
      align-items: center;
    }
    
    .partner-card {
      background: var(--white);
      padding: var(--spacing-lg);
      border-radius: var(--radius-lg);
      border: 1px solid var(--gray-200);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 150px;
      transition: var(--transition);
    }
    
    .partner-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary-color);
    }
    
    .partner-card img {
      max-width: 100%;
      max-height: 120px;
      object-fit: contain;
    }
    
    /* Footer */
    .footer {
      background: var(--gray-900);
      color: var(--white);
    }
    
    .footer-top {
      padding: var(--spacing-2xl) 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .footer-top .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-around;
      gap: var(--spacing-xl);
    }
    
    .footer-contact {
      display: flex;
      align-items: flex-start;
      gap: var(--spacing-md);
      flex: 1;
      min-width: 200px;
    }
    
    .footer-contact i {
      font-size: 1.5rem;
      color: var(--primary-color);
      margin-top: var(--spacing-xs);
    }
    
    .footer-contact h4 {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: var(--spacing-xs);
    }
    
    .footer-contact p {
      font-size: 0.875rem;
      opacity: 0.8;
      margin: 0;
    }
    
    .footer-divider {
      width: 1px;
      height: 80px;
      background: rgba(255, 255, 255, 0.2);
      display: none;
    }
    
    .footer-middle {
      padding: var(--spacing-2xl) 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .footer-middle .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: var(--spacing-2xl);
    }
    
    .footer-column h3 {
      font-family: 'Poppins', sans-serif;
      font-size: 1.125rem;
      font-weight: 600;
      margin-bottom: var(--spacing-md);
    }
    
    .footer-column ul {
      list-style: none;
      padding: 0;
    }
    
    .footer-column li {
      margin-bottom: var(--spacing-sm);
    }
    
    .footer-column a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-size: 0.875rem;
      transition: var(--transition);
    }
    
    .footer-column a:hover {
      color: var(--primary-color);
    }
    
    .payment-method img {
      max-width: 100%;
      max-height: 60px;
      object-fit: contain;
    }
    
    .payment-code {
      font-size: 0.875rem;
      color: var(--primary-color);
      font-weight: 600;
    }
    
    .footer-bottom {
      padding: var(--spacing-lg) 0;
      text-align: center;
      background: rgba(0, 0, 0, 0.2);
    }
    
    .footer-bottom .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 var(--spacing-lg);
    }
    
    .footer-bottom p {
      margin: 0;
      font-size: 0.875rem;
      opacity: 0.7;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
      .header-main .container {
        flex-direction: column;
        gap: var(--spacing-lg);
      }
      
      .search-bar {
        order: 3;
        width: 100%;
        max-width: none;
      }
      
      .nav-menu {
        flex-direction: column;
        width: 100%;
      }
      
      .nav-link {
        padding: var(--spacing-md) var(--spacing-lg);
        text-align: center;
      }
      
      .hero {
        min-height: 60vh;
      }
      
      .cta-buttons {
        flex-direction: column;
        align-items: stretch;
      }
      
      .mission-cards {
        grid-template-columns: 1fr;
        gap: var(--spacing-xl);
      }
      
      .categories {
        grid-template-columns: 1fr !important;
      }
      
      .toil {
        flex-direction: column !important;
      }
      
      .division {
        grid-template-columns: 1fr !important;
      }
      
      .fat {
        grid-template-columns: 1fr !important;
      }
      
      .divident {
        flex-direction: column !important;
      }
      
      .vl {
        display: none !important;
      }
    }
    </style>
  </div>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="header-top">
      <div class="container">
        <div>🔥 Professional Fire Safety Equipment & Services</div>
        <div>📞 Emergency: 0789059405 / 0786394551</div>
      </div>
    </div>
    <div class="header-main">
      <div class="container">
        <a href="website.php" class="logo">
          <img src="./images/Captured.JPG" alt="BAFRACOO Logo">
          <span class="logo-text">BAFRACOO</span>
        </a>
        
        <div class="search-bar">
          <form class="search-form" method="post">
            <input type="text" name="search" class="search-input" placeholder="Search fire safety equipment...">
            <button type="submit" class="search-btn">
              <i class="fas fa-search"></i>
              SEARCH
            </button>
          </form>
        </div>
        
        <div class="header-actions">
          <div class="support-info">
            <div class="support-title">SUPPORT</div>
            <div class="support-number">0789059405/0786394551</div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Navigation -->
  <nav class="nav">
    <div class="container">
      <ul class="nav-menu">
        <li class="nav-item">
          <a href="website.php" class="nav-link">HOME</a>
        </li>
        <li class="nav-item">
          <a href="#mission" class="nav-link">MISSION</a>
        </li>
        <li class="nav-item">
          <a href="#categories" class="nav-link">CATEGORIES</a>
        </li>
        <li class="nav-item">
          <a href="#services" class="nav-link">SERVICES</a>
        </li>
        <li class="nav-item">
          <a href="#partners" class="nav-link">PARTNERS</a>
        </li>
        <li class="nav-item">
          <a href="loginadmin.php" class="nav-link">ADMIN</a>
        </li>
        <li class="nav-item">
          <a href="USERS/loginuser.php" class="nav-link">LOGIN</a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="container">
      <div class="hero-content">
        <span class="hero-badge">🔥 Fire Safety Experts</span>
        <h1 class="hero-title">Fire Extinguishers<br>For Sale & Refill</h1>
        <p class="hero-subtitle">Professional Fire Safety Equipment</p>
        <p class="hero-description">CO₂: 3 - 50kg | Powder: 0.5 - 50kg<br>Protecting lives and property with quality fire safety solutions</p>
        <div class="cta-buttons">
          <a href="USERS/registrationuser.php" class="btn-primary">
            <i class="fas fa-shopping-cart"></i>
            SHOP NOW
          </a>
          <a href="#mission" class="btn-secondary">
            <i class="fas fa-info-circle"></i>
            LEARN MORE
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Mission Section -->
  <section id="mission" class="mission">
    <div class="container">
      <div class="mission-intro">
        <span class="mission-badge">About BAFRACOO</span>
        <h2 class="mission-title">30 Years of Excellence in Fire Safety</h2>
        <p class="mission-description">
          BAFRACOO has become one of the leading fire safety equipment suppliers in Kigali with 30 years of 
          experience in fire prevention, safety equipment, and emergency services.
        </p>
      </div>
      
      <div class="mission-cards">
        <div class="mission-card">
          <div class="mission-card-icon">
            <i class="fas fa-bullseye"></i>
          </div>
          <h3>MISSION</h3>
          <p>Our mission is to promote safety and security by providing high-quality fire safety equipment and professional services that protect lives and property.</p>
        </div>
        
        <div class="mission-card">
          <div class="mission-card-icon">
            <i class="fas fa-eye"></i>
          </div>
          <h3>VISION</h3>
          <p>To be the leading fire safety equipment provider in Rwanda, known for our reliability, quality products, and exceptional customer service.</p>
        </div>
        
        <div class="mission-card">
          <div class="mission-card-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h3>VALUES</h3>
          <p>Safety first, quality assurance, customer satisfaction, and continuous improvement in all our fire safety solutions and services.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Categories Section -->
  <section id="categories" class="categories-section">
    <div class="container">
      <h2 class="section-title">Top Categories</h2>
      <div class="categories">
        <div class="category-card">
          <div class="category-image">
            <img src="./images/hammer.jpeg" alt="Makita Tools - Corded">
          </div>
          <div class="category-content">
            <h3>Makita Tools - Corded</h3>
            <ul>
              <li>Hammer Drill</li>
              <li>Vacuum Cleaner</li>
              <li>Rotary Hammer</li>
              <li>Angle Grinder</li>
            </ul>
          </div>
        </div>

        <div class="category-card">
          <div class="category-image">
            <img src="./images/Cordless chain saw.jpeg" alt="Makita Tools - Cordless">
          </div>
          <div class="category-content">
            <h3>Makita Tools - Cordless</h3>
            <ul>
              <li>Angle Grinder</li>
              <li>Cordless Chain Saw</li>
              <li>Pressure Washer</li>
              <li>Hammer Driver Drill</li>
            </ul>
          </div>
        </div>

        <div class="category-card">
          <div class="category-image">
            <img src="./images/Cut-off wheel.jpeg" alt="Accessories">
          </div>
          <div class="category-content">
            <h3>Accessories</h3>
            <ul>
              <li>Abrasive Belt</li>
              <li>Bim Hole Saw</li>
              <li>Cut-off Wheel</li>
              <li>Center Caps</li>
            </ul>
          </div>
        </div>

        <div class="category-card">
          <div class="category-image">
            <img src="./images/Miniature Circuit Breakers.jpeg" alt="Safety Equipment">
          </div>
          <div class="category-content">
            <h3>Safety Equipment</h3>
            <ul>
              <li>Fire Extinguishers</li>
              <li>Fire Blanket</li>
              <li>Safety Jacket | Helmet | Shoes</li>
              <li>Fire Ball</li>
            </ul>
          </div>
        </div>

        <div class="category-card">
          <div class="category-image">
            <img src="./images/Fire extinguishers.jpeg" alt="Construction Tools">
          </div>
          <div class="category-content">
            <h3>Construction Tools</h3>
            <ul>
              <li>Wires for Fence</li>
              <li>Tiles</li>
              <li>Construction Plastic Roll</li>
              <li>Construction Scaffolding Net</li>
            </ul>
          </div>
        </div>

        <div class="category-card">
          <div class="category-image">
            <img src="./images/Led Home & Street Light.jpeg" alt="Electrical Tools">
          </div>
          <div class="category-content">
            <h3>Electrical Tools</h3>
            <ul>
              <li>Miniature Circuit Breakers</li>
              <li>Back up UPS</li>
              <li>Contactors & Thermal Relays</li>
              <li>Led Home & Street Light</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Featured Categories -->
  <section class="featured-section">
    <div class="container">
      <div class="featured-grid">
        <div class="featured-card featured-card-1">
          <div class="featured-content">
            <h3>Professional Tools & DIY Equipment</h3>
            <p>Just for your needs</p>
            <a href="USERS/registrationuser.php" class="btn-primary">SHOP NOW</a>
          </div>
        </div>
        <div class="featured-card featured-card-2">
          <div class="featured-content">
            <h3>Plumbing Materials & Bathroom Sets</h3>
            <p>Tiles, tubs, toilets & more</p>
            <a href="USERS/registrationuser.php" class="btn-primary">SHOP NOW</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <section id="services" class="services-section">
    <div class="container">
      <h2 class="section-title">Other Services</h2>
      <div class="services-grid">
        <div class="service-card">
          <div class="service-icon">
            <i class="fas fa-water"></i>
          </div>
          <h3>Fire Extinguisher Refill</h3>
          <p>Existing water, foam and powder extinguishers can potentially be refilled, not replaced.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <i class="fas fa-truck"></i>
          </div>
          <h3>Transportation</h3>
          <p>We offer land transport service and tailor-made distribution services across the entire African region.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <i class="fas fa-tools"></i>
          </div>
          <h3>Service Center for All Makita Machines</h3>
          <p>We provide repair services or provide maintenance for all Makita products in Rwanda.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-content">
        <h2>Live. Excel. Enjoy</h2>
        <p>Discover our complete range of products</p>
        <a href="USERS/registrationuser.php" class="btn-primary">SHOP NOW</a>
      </div>
    </div>
  </section>

  <!-- Partners Section -->
  <section id="partners" class="partners-section">
    <div class="container">
      <h2 class="section-title">Our Partners</h2>
      <div class="partners-grid">
        <div class="partner-card">
          <img src="./images/ff2a89dd1efe38d99fd4f9acb210e519.jpg" alt="Partner 1">
        </div>
        <div class="partner-card">
          <img src="./images/gacia-ensure-your-safety-200.png" alt="Partner 2">
        </div>
        <div class="partner-card">
          <img src="./images/sassin.png" alt="Partner 3">
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-top">
      <div class="container">
        <div class="footer-contact">
          <i class="fas fa-address-book"></i>
          <div>
            <h4>Address</h4>
            <p>KN 1 Rd 48, Kigali, Muhima road</p>
            <p>P.O BOX: 3290</p>
          </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-contact">
          <i class="fas fa-recycle"></i>
          <div>
            <h4>Repair & Maintenance</h4>
            <p>If goods have problems</p>
          </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-contact">
          <i class="fas fa-envelope"></i>
          <div>
            <h4>Get In Touch</h4>
            <p>info@bafraco.com</p>
          </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-contact">
          <i class="fas fa-phone"></i>
          <div>
            <h4>Call Us</h4>
            <p>0789059405 | 0786394551</p>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-middle">
      <div class="container">
        <div class="footer-column">
          <h3>Our Brands</h3>
          <ul>
            <li><a href="#">Makita</a></li>
            <li><a href="#">Sassin</a></li>
            <li><a href="#">Gacia</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h3>Company</h3>
          <ul>
            <li><a href="#">About Bafraco</a></li>
            <li><a href="#">Shop</a></li>
            <li><a href="#">Our Gallery</a></li>
            <li><a href="#">Contact Us</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h3>User Guidelines</h3>
          <ul>
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Terms & Conditions</a></li>
            <li><a href="#">FAQs</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h3>Payment Methods</h3>
          <p class="payment-method">
            <img src="./images/mtn-momo-1024x576.jpg" alt="MTN Mobile Money">
          </p>
          <!-- <p class="payment-code">*182*8*1*077780#</p> -->
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container">
        <p>&copy; 2024 Bafraco Ltd. All Rights Reserved.</p>
      </div>
    </div>
  </footer>  <!-- Ion Icons -->
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  <!-- Smooth Scrolling -->
  <script>
    // Smooth scrolling for navigation links
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

    // Animation on scroll
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.animation = 'fadeIn 1s ease-out forwards';
        }
      });
    }, observerOptions);

    // Observe mission cards
    document.querySelectorAll('.mission-card').forEach(card => {
      observer.observe(card);
    });
    
    // Observe category cards
    document.querySelectorAll('.category-card').forEach(card => {
      observer.observe(card);
    });
    
    // Observe service cards
    document.querySelectorAll('.service-card').forEach(card => {
      observer.observe(card);
    });
  </script>
</body>
</html>