(() =>
{
    'use strict';

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

    const hexToRgb = (hex) =>
    {
        const match = /^#([0-9a-f]{6})$/i.exec((hex || '').trim());
        if (!match) return null;
        const n = parseInt(match[1], 16);
        return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
    };

    const rgbToHex = (r, g, b) =>
        '#' + [r, g, b].map(v => clamp(Math.round(v), 0, 255).toString(16).padStart(2, '0')).join('');

    const rgbToHsv = (r, g, b) =>
    {
        r /= 255; g /= 255; b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
        let h = 0;
        if (d)
        {
            if (max === r) h = ((g - b) / d) % 6;
            else if (max === g) h = (b - r) / d + 2;
            else h = (r - g) / d + 4;
            h *= 60;
            if (h < 0) h += 360;
        }
        return { h, s: max ? d / max : 0, v: max };
    };

    const hsvToRgb = (h, s, v) =>
    {
        const c = v * s;
        const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
        const m = v - c;
        let rp = 0, gp = 0, bp = 0;
        if (h < 60) [rp, gp, bp] = [c, x, 0];
        else if (h < 120) [rp, gp, bp] = [x, c, 0];
        else if (h < 180) [rp, gp, bp] = [0, c, x];
        else if (h < 240) [rp, gp, bp] = [0, x, c];
        else if (h < 300) [rp, gp, bp] = [x, 0, c];
        else [rp, gp, bp] = [c, 0, x];
        return { r: (rp + m) * 255, g: (gp + m) * 255, b: (bp + m) * 255 };
    };

    const updatePicker = (picker, hsv, writeHex = true) =>
    {
        const rgb = hsvToRgb(hsv.h, hsv.s, hsv.v);
        const hex = rgbToHex(rgb.r, rgb.g, rgb.b);
        const hexInput = picker.querySelector('.js-wxColorHex');
        const swatch = picker.querySelector('.js-wxColorToggle');
        const wheel = picker.querySelector('.js-wxColorWheel');
        const pointer = picker.querySelector('.js-wxColorPointer');
        const brightness = picker.querySelector('.js-wxColorBrightness');
        const rgbInputs = picker.querySelectorAll('.js-wxColorRgb');

        picker._wxHsv = hsv;
        if (writeHex && hexInput) hexInput.value = hex;
        if (swatch) swatch.style.backgroundColor = hex;
        if (brightness) brightness.value = Math.round(hsv.v * 100);
        if (rgbInputs.length === 3)
        {
            rgbInputs[0].value = Math.round(rgb.r);
            rgbInputs[1].value = Math.round(rgb.g);
            rgbInputs[2].value = Math.round(rgb.b);
        }

        if (wheel && pointer)
        {
            const angle = hsv.h * Math.PI / 180;
            const radius = hsv.s * 50;
            pointer.style.left = (50 + Math.cos(angle) * radius) + '%';
            pointer.style.top = (50 + Math.sin(angle) * radius) + '%';
        }
    };

    const initPicker = (picker) =>
    {
        if (picker.dataset.wxReady === '1') return;
        picker.dataset.wxReady = '1';

        const hexInput = picker.querySelector('.js-wxColorHex');
        const rgb = hexToRgb(hexInput ? hexInput.value : '') || { r: 79, g: 70, b: 229 };
        updatePicker(picker, rgbToHsv(rgb.r, rgb.g, rgb.b), false);

        const wheel = picker.querySelector('.js-wxColorWheel');
        const pickFromPointer = (event) =>
        {
            const rect = wheel.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const dx = event.clientX - cx;
            const dy = event.clientY - cy;
            const maxR = rect.width / 2;
            const saturation = clamp(Math.sqrt(dx * dx + dy * dy) / maxR, 0, 1);
            let hue = Math.atan2(dy, dx) * 180 / Math.PI;
            if (hue < 0) hue += 360;
            const hsv = picker._wxHsv || { h: 0, s: 0, v: 1 };
            updatePicker(picker, { h: hue, s: saturation, v: hsv.v });
        };

        if (wheel)
        {
            wheel.addEventListener('pointerdown', (event) =>
            {
                event.preventDefault();
                wheel.setPointerCapture(event.pointerId);
                pickFromPointer(event);
            });
            wheel.addEventListener('pointermove', (event) =>
            {
                if (wheel.hasPointerCapture(event.pointerId)) pickFromPointer(event);
            });
        }

        const brightness = picker.querySelector('.js-wxColorBrightness');
        if (brightness)
        {
            brightness.addEventListener('input', () =>
            {
                const hsv = picker._wxHsv || { h: 0, s: 0, v: 1 };
                updatePicker(picker, { h: hsv.h, s: hsv.s, v: clamp(Number(brightness.value) / 100, 0, 1) });
            });
        }

        if (hexInput)
        {
            hexInput.addEventListener('input', () =>
            {
                const value = hexToRgb(hexInput.value);
                if (value) updatePicker(picker, rgbToHsv(value.r, value.g, value.b), false);
            });
        }

        picker.querySelectorAll('.js-wxColorRgb').forEach(input =>
        {
            input.addEventListener('input', () =>
            {
                const values = Array.from(picker.querySelectorAll('.js-wxColorRgb')).map(i => clamp(Number(i.value) || 0, 0, 255));
                if (values.length === 3)
                {
                    const hsv = rgbToHsv(values[0], values[1], values[2]);
                    updatePicker(picker, hsv);
                }
            });
        });
    };

    const initAll = (root = document) =>
    {
        root.querySelectorAll('[data-wx-color-picker]').forEach(initPicker);
    };

    document.addEventListener('click', (event) =>
    {
        const toggle = event.target.closest('.js-wxColorToggle');
        if (toggle)
        {
            const picker = toggle.closest('[data-wx-color-picker]');
            const panel = picker && picker.querySelector('.js-wxColorPanel');
            if (panel)
            {
                document.querySelectorAll('.js-wxColorPanel:not([hidden])').forEach(p => { if (p !== panel) p.hidden = true; });
                panel.hidden = !panel.hidden;
            }
            return;
        }

        const iconButton = event.target.closest('[data-wx-icon-value]');
        if (iconButton)
        {
            const picker = iconButton.closest('[data-wx-icon-picker]');
            const input = picker && picker.querySelector('.js-wxIconValue');
            if (input)
            {
                input.value = iconButton.dataset.wxIconValue || '';
                picker.querySelectorAll('[data-wx-icon-value]').forEach(button => button.classList.toggle('is-selected', button === iconButton));
            }
            return;
        }

        if (!event.target.closest('[data-wx-color-picker]'))
        {
            document.querySelectorAll('.js-wxColorPanel:not([hidden])').forEach(panel => { panel.hidden = true; });
        }
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initAll());
    else initAll();

    new MutationObserver(mutations =>
    {
        for (const mutation of mutations)
        {
            mutation.addedNodes.forEach(node =>
            {
                if (node.nodeType === 1)
                {
                    if (node.matches && node.matches('[data-wx-color-picker]')) initPicker(node);
                    initAll(node);
                }
            });
        }
    }).observe(document.documentElement, { childList: true, subtree: true });
})();
