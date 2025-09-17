import './bootstrap';
import './alpine';
import './vue';
import './avatarSync';
import './bookmarks';
import './export-handler';
import './components/search-autocomplete';
import './global-keyboard-shortcuts';

// Import SweetAlert2
import Swal from 'sweetalert2';

// Import Toast Helper
import toast from './toast-helper';

// Import GSAP for advanced animations
import { gsap } from 'gsap';
import { TextPlugin } from 'gsap/TextPlugin';
import { SplitText } from 'gsap/SplitText';

// Register GSAP plugins
gsap.registerPlugin(TextPlugin);
if (typeof SplitText !== 'undefined') {
    gsap.registerPlugin(SplitText);
}

// Import motion.js for animations
import {
    animate,
    hover,
    inView,
    easeIn,
    easeOut,
    easeInOut,
    backIn,
    backOut,
    backInOut,
    circIn,
    circOut,
    circInOut,
    anticipate,
    spring,
    stagger,
    cubicBezier,
} from 'motion';

// Make GSAP available globally
window.gsap = gsap;

// Make SweetAlert2 available globally
window.Swal = Swal;

// Make Toast helper available globally
window.toast = toast;

// Make motion available globally for Alpine.js
window.motion = {
    animate: animate,
    hover: hover,
    inView: inView,
    easeIn: easeIn,
    easeOut: easeOut,
    easeInOut: easeInOut,
    backOut: backOut,
    backIn: backIn,
    backInOut: backInOut,
    circIn: circIn,
    circOut: circOut,
    circInOut: circInOut,
    anticipate: anticipate,
    spring: spring,
    stagger: stagger,
    cubicBezier: cubicBezier,
};

// Initialize GSAP animations on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Fancy h1 animation for hero section
    const heroTitle = document.querySelector('#hero-title');
    if (heroTitle) {
        // Split text into spans for each character - trim whitespace first
        const text = heroTitle.textContent.trim();
        heroTitle.innerHTML = text.split('').map((char, i) =>
            `<span class="hero-char" style="display: inline-block;">${char === ' ' ? '&nbsp;' : char}</span>`
        ).join('');
        
        // Create timeline for orchestrated animation
        const tl = gsap.timeline();
        
        // Animate each character with stagger
        tl.from('.hero-char', {
            opacity: 0,
            scale: 0,
            y: 80,
            rotationX: -90,
            transformOrigin: "0% 50% -50",
            ease: "back.out(1.7)",
            stagger: {
                amount: 1.5,
                grid: "auto",
                from: "random"
            },
            duration: 1.2
        })
        .to('.hero-char', {
            color: () => gsap.utils.random(['#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B']),
            stagger: {
                each: 0.02,
                from: "center",
                grid: "auto",
                repeat: -1,
                yoyo: true,
                repeatDelay: 3
            },
            duration: 0.5
        }, "+=0.5");
        
        // Add hover effect to individual characters
        document.querySelectorAll('.hero-char').forEach(char => {
            char.addEventListener('mouseenter', function() {
                gsap.to(this, {
                    scale: 1.3,
                    rotation: gsap.utils.random(-10, 10),
                    color: '#3B82F6',
                    duration: 0.3,
                    ease: "power2.out"
                });
            });
            
            char.addEventListener('mouseleave', function() {
                gsap.to(this, {
                    scale: 1,
                    rotation: 0,
                    duration: 0.3,
                    ease: "power2.inOut"
                });
            });
        });
    }
    
    // Additional fancy animations for other hero elements
    const heroSubtitle = document.querySelector('.hero-subtitle');
    if (heroSubtitle) {
        gsap.from(heroSubtitle, {
            opacity: 0,
            y: 50,
            scale: 0.9,
            duration: 1,
            delay: 1.5,
            ease: "power4.out"
        });
    }
    
    // Animate hero description
    const heroDescription = document.querySelector('.hero-description');
    if (heroDescription) {
        gsap.from(heroDescription, {
            opacity: 0,
            y: 40,
            duration: 1,
            delay: 1.8,
            ease: "power3.out"
        });
    }
    
    // Animate CTA buttons with bounce effect
    const ctaButtons = document.querySelectorAll('.hero-cta-button');
    if (ctaButtons.length > 0) {
        gsap.from(ctaButtons, {
            opacity: 0,
            scale: 0,
            rotation: -180,
            stagger: 0.2,
            duration: 0.8,
            delay: 2,
            ease: "back.out(1.7)"
        });
    }
});
