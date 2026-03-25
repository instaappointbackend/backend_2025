@extends('layouts.app')

@section('title', 'InstaAppoint - Quick Appointment Booking')

@section('styles')
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .hero-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 0 0;
            position: relative;
        }

        /* Make sure content is clickable */
        .hero-section .container {
            position: relative;
            z-index: 2;
        }

        /* Disable click blocking from wave */
        .hero-section::after {
            pointer-events: none;
            z-index: 1;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 67px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100"><path fill="%23ffffff" d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,100L1360,100C1280,100,1120,100,960,100C800,100,640,100,480,100C320,100,160,100,80,100L0,100Z"></path></svg>');
            background-size: cover;
            background-repeat: no-repeat;
        }

        .logo {
            max-width: 350px;
            height: auto;
        }

        .logo-icon {
            max-width: 80px;
            height: auto;
        }

        .download-badge {
            width: 160px;
            transition: transform 0.3s ease;
        }

        .download-badge:hover {
            transform: scale(1.05);
        }

        .feature-icon {
            background-color: var(--primary-color);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .qr-code {
            max-width: 150px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 10px;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .app-section {
            background-color: #f8f9fa;
            border-radius: 20px;
            overflow: hidden;
        }

        .phone-mockup {
            max-width: 300px;
            position: relative;
            z-index: 2;
        }

        .fas {
            padding: 0 4px
        }

        .wave-bg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 0;
        }

        footer {
            background-color: var(--secondary-color);
            color: white;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            transition: background-color 0.3s ease;
        }

        .social-icon:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-link {
            color: var(--secondary-color);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 30px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .newsletter-section {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
            border-radius: 15px;
            padding: 40px;
            margin-top: 30px;
        }

        .newsletter-card {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
    </style>
@endsection

@section('content')
    <div class="bg-light">&nbsp;&nbsp;</div>
    <div class="bg-light">&nbsp;&nbsp;</div>
    @include('public.imageCarousal')
    @include('public.socialPage.pricing')
    <div>&nbsp;&nbsp;</div>
    <div>&nbsp;&nbsp;</div>
@endsection
