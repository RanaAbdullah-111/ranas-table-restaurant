/**
 * auth.js — Shared interactive behavior for Rana's Table authentication screens
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Generate Luxury Embers
    const emberColors = ['#C9A84C', '#FF8C42', '#FFD166', '#E87040'];
    const emberCount = window.innerWidth < 600 ? 14 : 22;
    for (let i = 0; i < emberCount; i++) {
        const ember = document.createElement('div');
        ember.className = 'ember';
        const size = 1.5 + Math.random() * 3;
        const color = emberColors[Math.floor(Math.random() * emberColors.length)];
        Object.assign(ember.style, {
            width: size + 'px',
            height: size + 'px',
            background: color,
            left: (4 + Math.random() * 92) + '%',
            bottom: '-8px',
            '--d': ((Math.random() - 0.5) * 70) + 'px',
            animationDuration: (8 + Math.random() * 10) + 's',
            animationDelay: (Math.random() * 16) + 's',
            boxShadow: `0 0 ${size * 2.5}px ${color}`
        });
        document.body.appendChild(ember);
    }

    // 2. Generate Candles Strip
    const candleStrip = document.getElementById('candleStrip');
    if (candleStrip) {
        const heights = [55, 85, 45, 100, 70, 60, 90, 50, 80];
        heights.forEach(h => {
            const c = document.createElement('div');
            c.className = 'candle';
            c.style.opacity = (0.07 + Math.random() * 0.1).toFixed(2);
            c.innerHTML = `
                <div class="flame" style="animation-delay:${(Math.random() * 0.8).toFixed(2)}s;animation-duration:${(1.2 + Math.random() * 0.8).toFixed(2)}s"></div>
                <div class="c-body" style="height:${h}px"></div>
                <div class="c-base"></div>
            `;
            candleStrip.appendChild(c);
        });
    }

    // 3. Password Show/Hide Toggle
    const pwToggle = document.getElementById('pwToggle');
    const pwField = document.getElementById('pwField') || document.getElementById('password');
    if (pwToggle && pwField) {
        pwToggle.addEventListener('click', () => {
            const isPassword = pwField.type === 'password';
            pwField.type = isPassword ? 'text' : 'password';
            pwToggle.textContent = isPassword ? '🙈' : '👁';
        });
    }

    // 4. Password Strength Meter
    if (pwField && document.getElementById('pb1')) {
        const bars = ['pb1', 'pb2', 'pb3', 'pb4'].map(id => document.getElementById(id));
        const pwLbl = document.getElementById('pwLabel');

        pwField.addEventListener('input', () => {
            const v = pwField.value;
            bars.forEach(b => b.className = 'pw-bar');
            if (!v) {
                if (pwLbl) pwLbl.textContent = '';
                return;
            }

            let score = 0;
            if (v.length >= 6) score++;
            if (v.length >= 8) score++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            let level = 1;
            if (score >= 5) level = 4;
            else if (score >= 3) level = 3;
            else if (score >= 2) level = 2;
            else level = 1;

            const cls = ['', 'weak', 'fair', 'good', 'strong'];
            const lbl = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const clr = ['', '#e05c3a', '#e0a93a', '#C9A84C', '#5ab97a'];

            for (let i = 0; i < level; i++) {
                if (bars[i]) bars[i].classList.add(cls[level]);
            }
            if (pwLbl) {
                pwLbl.textContent = lbl[level];
                pwLbl.style.color = clr[level];
            }
        });
    }

    // 5. Submit Button Loading Spinner
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function () {
            const btn = this.querySelector('.btn-primary, .btn-submit');
            if (btn && !this.dataset.noSpinner) {
                btn.classList.add('loading');
            }
        });
    });
});
