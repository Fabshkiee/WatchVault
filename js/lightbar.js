/**
 * Particle Effect Logic
 * Adapted for WatchVault Dashboard from pstream movie site hehe thanku opensource
 */

class Particle {
    x = 0;
    y = 0;
    radius = 0;
    direction = 0;
    speed = 0;
    lifetime = 0;
    ran = 0;
    size = 10;
    options;

    constructor(canvas, options = {}) {
        this.options = options;
        this.reset(canvas); 
        this.initialize(canvas);
    }

    reset(canvas) {
        this.x = Math.round((Math.random() * canvas.width) / 2 + canvas.width / 4);
        this.y = Math.random() * 100 + 5; 

        this.radius = 1 + Math.floor(Math.random() * 0.5);
        this.direction = (Math.random() * Math.PI) / 2 + Math.PI / 4; 
        this.speed = 0.02 + Math.random() * 0.085; // Slow base speed

        const second = 65;
        this.lifetime = second * 3 + Math.random() * (second * 30);

        this.size = this.options.sizeRange
            ? Math.random() * (this.options.sizeRange[1] - this.options.sizeRange[0]) + this.options.sizeRange[0]
            : 10;

        // Custom motion for specific asset types if needed, generally false for falling items
        if (this.options.horizontalMotion) {
            this.direction = Math.random() <= 0.5 ? 0 : Math.PI;
            this.y = Math.random() * canvas.height; 
            this.lifetime = 30 * second;
        }

        this.ran = 0;
    }

    initialize(canvas) {
        this.ran = Math.random() * this.lifetime;
        const baseSpeed = this.speed;
        // Large speed applied *once* to scatter particles initially
        this.speed = Math.random() * this.lifetime * baseSpeed;
        this.update(canvas);
        this.speed = baseSpeed; // Reset to slow speed
    }

    update(canvas) {
        this.ran += 1;

        const addX = this.speed * Math.cos(this.direction);
        const addY = this.speed * Math.sin(this.direction);
        this.x += addX;
        this.y += addY; // Particles fall DOWN

        if (this.ran > this.lifetime || this.y > canvas.height + this.size) {
            this.reset(canvas);
        }
    }

    render(canvas) {
        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        ctx.save();
        ctx.beginPath();

        const x = this.ran / this.lifetime;
        const o = (x - x * x) * 4; 
        ctx.globalAlpha = Math.max(0, o * 0.8);

        if (this.options.imgSrc) {
            const img = new Image();
            img.src = this.options.imgSrc;
            
            ctx.translate(this.x, this.y);
            const w = this.size;
            const h = w; 
            
            // Draw image instead of text/emoji
            // We use the loaded image if cached, otherwise this might flicker on first load
            // Ideally images should be preloaded, but for this effect, drawing the Image object works 
            // if the browser cache handles it fast enough (which it usually does for repeated assets).
            
            // Rotation logic
            ctx.rotate(this.direction - Math.PI); 
            ctx.drawImage(img, -w / 2, -h / 2, w, h);

        } else {
            // Default white dot fallback
            ctx.ellipse(this.x, this.y, this.radius, this.radius * 1.5, this.direction, 0, Math.PI * 2);
            ctx.fillStyle = "white";
            ctx.fill();
        }
        ctx.restore();
    }
}

function runParticleEffect() {
    const canvas = document.querySelector(".particles");
    if (!canvas) return;
    
    const ctx = canvas.getContext("2d");
    if (!ctx) return;
    
    const particles = [];

    // Set canvas size
    canvas.width = canvas.getBoundingClientRect().width;
    canvas.height = 300; 

    const particleCount = 265;
    
    // Define the assets
    const assets = [
        { image: "../assets/watchvault-logo.svg", sizeRange: [15, 30] },
        { image: "../assets/watchvault-logo.svg", sizeRange: [15, 30] },
        { image: "../assets/watchvault-logo.svg", sizeRange: [15, 30] },
        { image: "../assets/watchvault-logo.svg", sizeRange: [15, 30] }
    ];

    // Percentage of particles that should be images (e.g., 15%)
    let imageParticleCount = particleCount * 0.15; 

    // Create particles
    for (let i = 0; i < particleCount; i += 1) {
        // Determine if this particle should be an image
        const isImageParticle = i <= imageParticleCount;
        
        let particleOptions = {};
        
        if (isImageParticle) {
            // Pick a random asset
            const randomAsset = assets[Math.floor(Math.random() * assets.length)];
            particleOptions = {
                imgSrc: randomAsset.image,
                sizeRange: randomAsset.sizeRange,
                horizontalMotion: false 
            };
        }

        const particle = new Particle(canvas, particleOptions);
        particles.push(particle);
    }

    // --- SPEED FIX LOGIC ---
    let shouldTick = true;
    let handle = null;
    
    function particlesLoop() {
        if (!ctx || !canvas) return;

        // Only update physics if shouldTick is true (limits physics calculations)
        if (shouldTick) {
            for (const particle of particles) {
                particle.update(canvas);
            }
            shouldTick = false;
        }

        // Always render
        const rect = canvas.getBoundingClientRect();
        if (rect.width !== canvas.width || 300 !== canvas.height) {
            canvas.width = rect.width;
            canvas.height = 300;
        }
        ctx.clearRect(0, 0, canvas.width, canvas.height); 
        
        for (const particle of particles) {
            particle.render(canvas);
        }

        handle = requestAnimationFrame(particlesLoop);
    }
    
    // This sets shouldTick to true 120 times per second (physics tick rate)
    const interval = setInterval(() => {
        shouldTick = true;
    }, 1e3 / 120); 

    particlesLoop(); // Start render loop

    const resizeObserver = new ResizeObserver(() => {
        if (canvas) {
            canvas.width = canvas.getBoundingClientRect().width;
            canvas.height = 300;
        }
    });
    resizeObserver.observe(canvas);

    // Return a cleanup function
    return () => {
        if (handle) cancelAnimationFrame(handle);
        clearInterval(interval);
        resizeObserver.disconnect();
    };
}

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    let cleanupEffect = runParticleEffect();
    
    // Re-run if the window resizes
    window.addEventListener('resize', () => {
        if (cleanupEffect) cleanupEffect(); 
        cleanupEffect = runParticleEffect(); 
    });
});