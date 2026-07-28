// public/js/pickers.js — date/heure custom (couleurs site)
document.addEventListener('DOMContentLoaded', () => {
    initTimePickers();
    initDatePickers();
});

function initTimePickers() {
    document.querySelectorAll('[data-time-picker]').forEach((wrap) => {
        const hidden = wrap.querySelector('[data-time-value]');
        if (!hidden) return;

        const hours = JSON.parse(wrap.dataset.hours || '[]');
        const minutes = JSON.parse(wrap.dataset.minutes || '[]');
        const required = wrap.dataset.required === '1';
        const units = {
            hour: wrap.querySelector('[data-time-unit="hour"]'),
            minute: wrap.querySelector('[data-time-unit="minute"]'),
        };

        let hourVal = '';
        let minuteVal = '';
        if (hidden.value && /^\d{2}:\d{2}$/.test(hidden.value)) {
            hourVal = hidden.value.slice(0, 2);
            minuteVal = hidden.value.slice(3, 5);
        }

        function syncHidden() {
            hidden.value = (hourVal !== '' && minuteVal !== '') ? `${hourVal}:${minuteVal}` : '';
        }

        function closeAll(except = null) {
            wrap.querySelectorAll('.time-picker-unit').forEach((unit) => {
                if (unit === except) return;
                const panel = unit.querySelector('.time-picker-panel');
                const trigger = unit.querySelector('.time-picker-trigger');
                if (panel) panel.hidden = true;
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            });
        }

        function renderPanel(unitKey) {
            const unit = units[unitKey];
            if (!unit) return;
            const panel = unit.querySelector('.time-picker-panel');
            const options = unitKey === 'hour' ? hours : minutes;
            const current = unitKey === 'hour' ? hourVal : minuteVal;
            let html = '';
            options.forEach((opt) => {
                const selected = current === opt ? ' is-selected' : '';
                html += `<button type="button" class="time-picker-option${selected}" role="option" data-value="${opt}" aria-selected="${current === opt ? 'true' : 'false'}">${opt}</button>`;
            });
            panel.innerHTML = html;
        }

        function setUnitValue(unitKey, value) {
            if (unitKey === 'hour') hourVal = value;
            else minuteVal = value;

            const unit = units[unitKey];
            const label = unit.querySelector('[data-time-label]');
            label.textContent = value;
            label.classList.remove('time-picker-placeholder');
            syncHidden();
            closeAll();
        }

        Object.keys(units).forEach((unitKey) => {
            const unit = units[unitKey];
            if (!unit) return;
            const trigger = unit.querySelector('.time-picker-trigger');
            const panel = unit.querySelector('.time-picker-panel');

            trigger.addEventListener('click', () => {
                const open = !panel.hidden;
                closeAll();
                if (!open) {
                    renderPanel(unitKey);
                    panel.hidden = false;
                    trigger.setAttribute('aria-expanded', 'true');
                    const selected = panel.querySelector('.is-selected');
                    if (selected) selected.scrollIntoView({ block: 'nearest' });
                }
            });

            panel.addEventListener('click', (e) => {
                const opt = e.target.closest('.time-picker-option');
                if (!opt) return;
                setUnitValue(unitKey, opt.dataset.value);
            });
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) closeAll();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAll();
        });

        const form = wrap.closest('form');
        if (form && required) {
            form.addEventListener('submit', (e) => {
                if (!hidden.value) {
                    e.preventDefault();
                    const hourTrigger = units.hour?.querySelector('.time-picker-trigger');
                    hourTrigger?.focus();
                    hourTrigger?.click();
                }
            });
        }
    });
}

function initDatePickers() {
    const moisNoms = [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
    ];
    const joursNoms = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'];

    document.querySelectorAll('[data-date-picker]').forEach((wrap) => {
        const hidden = wrap.querySelector('[data-date-value]');
        const trigger = wrap.querySelector('.date-picker-trigger');
        const panel = wrap.querySelector('.date-picker-panel');
        const label = wrap.querySelector('.date-picker-label');
        if (!hidden || !trigger || !panel || !label) return;

        const min = wrap.dataset.min || '';
        const max = wrap.dataset.max || '';
        let viewYear;
        let viewMonth;

        function parseYmd(s) {
            if (!s || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null;
            const [y, m, d] = s.split('-').map(Number);
            return new Date(y, m - 1, d);
        }

        function formatYmd(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function formatDisplay(ymd) {
            const dt = parseYmd(ymd);
            if (!dt) return '';
            return dt.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
        }

        function isDisabled(date) {
            const ymd = formatYmd(date);
            if (min && ymd < min) return true;
            if (max && ymd > max) return true;
            return false;
        }

        function setValue(ymd) {
            hidden.value = ymd || '';
            if (ymd) {
                label.textContent = formatDisplay(ymd);
                label.classList.remove('date-picker-placeholder');
            } else {
                label.textContent = 'JJ/MM/AAAA';
                label.classList.add('date-picker-placeholder');
            }
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function closePanel() {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }

        function openPanel() {
            const selected = parseYmd(hidden.value);
            const today = new Date();
            const base = selected || parseYmd(min) || today;
            viewYear = base.getFullYear();
            viewMonth = base.getMonth();
            renderCalendar();
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
        }

        function renderCalendar() {
            const first = new Date(viewYear, viewMonth, 1);
            let startOffset = first.getDay() - 1;
            if (startOffset < 0) startOffset = 6;

            const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
            const selected = hidden.value;

            let html = '<div class="date-picker-header">';
            html += '<button type="button" class="date-picker-nav" data-nav="-1" aria-label="Mois précédent">‹</button>';
            html += `<span class="date-picker-month">${moisNoms[viewMonth]} ${viewYear}</span>`;
            html += '<button type="button" class="date-picker-nav" data-nav="1" aria-label="Mois suivant">›</button>';
            html += '</div>';
            html += '<div class="date-picker-weekdays">';
            joursNoms.forEach((j) => {
                html += `<span>${j}</span>`;
            });
            html += '</div><div class="date-picker-grid">';

            for (let i = 0; i < startOffset; i++) {
                html += '<span class="date-picker-empty"></span>';
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const date = new Date(viewYear, viewMonth, d);
                const ymd = formatYmd(date);
                const disabled = isDisabled(date);
                const isSelected = selected === ymd;
                const today = formatYmd(new Date()) === ymd;
                let classes = 'date-picker-day';
                if (disabled) classes += ' is-disabled';
                if (isSelected) classes += ' is-selected';
                if (today) classes += ' is-today';
                html += `<button type="button" class="${classes}" data-date="${ymd}" ${disabled ? 'disabled' : ''}>${d}</button>`;
            }

            html += '</div>';
            if (!hidden.required) {
                html += '<button type="button" class="date-picker-clear">Effacer</button>';
            }
            panel.innerHTML = html;
        }

        trigger.addEventListener('click', () => {
            if (panel.hidden) openPanel();
            else closePanel();
        });

        panel.addEventListener('click', (e) => {
            const nav = e.target.closest('[data-nav]');
            if (nav) {
                viewMonth += Number(nav.dataset.nav);
                if (viewMonth < 0) {
                    viewMonth = 11;
                    viewYear -= 1;
                } else if (viewMonth > 11) {
                    viewMonth = 0;
                    viewYear += 1;
                }
                renderCalendar();
                return;
            }

            const dayBtn = e.target.closest('.date-picker-day:not(.is-disabled)');
            if (dayBtn) {
                setValue(dayBtn.dataset.date);
                closePanel();
                return;
            }

            if (e.target.closest('.date-picker-clear')) {
                setValue('');
                closePanel();
            }
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) closePanel();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !panel.hidden) {
                closePanel();
                trigger.focus();
            }
        });

        const form = wrap.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (hidden.required && !hidden.value) {
                    e.preventDefault();
                    openPanel();
                    trigger.focus();
                }
            });
        }
    });
}
