// ==UserScript==
// @name         DirectAdmin Softaculous WordPress Install Autofill
// @namespace    https://uk-shared01.dapanel.net/
// @version      1.1.0
// @description  Staged, verified WordPress installer autofill. Does NOT submit the installation.
// @match        https://uk-shared01.dapanel.net/*
// @run-at       document-idle
// @grant        none
// ==/UserScript==

(() => {
  'use strict';

  // CUSTOMISE THESE TWO VALUES. Admin password is requested on each run.
  const ADMIN_USERNAME = 'TD-Support';
  const ADMIN_EMAIL = 'hello@reallyusefulplugins.com';
  const WAIT_AFTER_VERSION_MS = 1800;
  const WAIT_AFTER_DOMAIN_MS = 1000;

  const $ = id => document.getElementById(id);
  const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
  const dispatch = (el, type) => el.dispatchEvent(new Event(type, { bubbles: true }));

  function setField(id, value, sendChange = true) {
    const el = $(id);
    if (!el) return false;
    // Use the native setter for compatibility with form libraries.
    const proto = el instanceof HTMLSelectElement ? HTMLSelectElement.prototype : HTMLInputElement.prototype;
    const setter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;
    if (setter) setter.call(el, String(value)); else el.value = String(value);
    dispatch(el, 'input');
    if (sendChange) dispatch(el, 'change');
    return true;
  }

  function setCheck(id, desired) {
    const el = $(id);
    if (!el) return false;
    if (el.checked !== desired) {
      el.checked = desired;
      dispatch(el, 'input');
      dispatch(el, 'change');
    }
    return true;
  }

  function humanise(domain) {
    const label = domain.replace(/^www\./i, '').split('.')[0] || domain;
    return label.split(/[-_]+/).filter(Boolean).map(p =>
      /^(sc|wp|api|ssl|crm|seo|pdf|sms)$/i.test(p)
        ? p.toUpperCase()
        : p[0].toUpperCase() + p.slice(1).toLowerCase()
    ).join(' ');
  }

  function compareVersion(a, b) {
    const x = String(a).match(/\d+/g)?.map(Number) || [];
    const y = String(b).match(/\d+/g)?.map(Number) || [];
    for (let i = 0; i < Math.max(x.length, y.length); i++) {
      const diff = (x[i] || 0) - (y[i] || 0);
      if (diff) return diff;
    }
    return 0;
  }

  function removePluginSelections() {
    // The plugin cards use custom click handlers, so DO NOT click the labels.
    const checkboxes = [...document.querySelectorAll('.plugins-list input[type="checkbox"]')];
    for (const el of checkboxes) {
      if (el.checked) {
        el.checked = false;
        dispatch(el, 'input');
        dispatch(el, 'change');
      }
      el.removeAttribute('checked');
    }
    // Includes Loginizer's second, independently controlled Social Login box.
    for (const id of ['loginizer', 'lz-social-login']) setCheck(id, false);
    document.querySelectorAll('.plugins-list').forEach(card => {
      card.style.backgroundColor = '';
      card.style.border = '1px solid rgba(157,152,152,.47)';
      card.querySelectorAll('.sai_checkbox_tick, .fas.fa-check-square')
        .forEach(icon => icon.style.display = 'none');
      const status = card.querySelector('.lz-social-status span');
      if (status) { status.textContent = 'Disabled'; status.style.color = 'red'; }
    });
    return checkboxes.length;
  }

  function verify(expected) {
    const checks = [
      ['Version', $('softbranch')?.value === expected.version],
      ['Domain', $('softdomain')?.value === expected.domain],
      ['Protocol (HTTPS)', $('softproto')?.value === expected.protocol],
      ['Install directory (empty)', $('softdirectory')?.value === ''],
      ['Site name', $('site_name')?.value === expected.name],
      ['Site description', $('site_desc')?.value === expected.domain],
      ['Enable Multisite', $('multisite')?.checked === true],
      ['Disable WP-Cron', $('disable_wp_cron')?.checked === true],
      ['Admin username', $('admin_username')?.value === ADMIN_USERNAME],
      ['Admin email', $('admin_email')?.value === ADMIN_EMAIL],
      ['Admin password', $('admin_pass')?.value === expected.password],
      ['No optional plugins checked', [...document.querySelectorAll('.plugins-list input[type="checkbox"]')].every(e => !e.checked)]
    ];
    return checks;
  }

  async function run(button) {
    const needed = ['softbranch', 'softproto', 'softdomain', 'softdirectory',
      'site_name', 'site_desc', 'multisite', 'disable_wp_cron',
      'admin_username', 'admin_pass', 'admin_email'];
    const missing = needed.filter(id => !$(id));
    if (missing.length) { alert('Softaculous fields missing: ' + missing.join(', ') + '\nOpen the full WordPress installation form.'); return; }
    const password = prompt('WordPress admin password (not saved):');
    if (password === null) return;
    if (password.length < 12) { alert('Use an admin password of at least 12 characters. Nothing changed.'); return; }
    if (!ADMIN_USERNAME || !ADMIN_EMAIL.includes('@')) { alert('Set ADMIN_USERNAME and ADMIN_EMAIL in the userscript first.'); return; }

    button.disabled = true;
    button.textContent = 'Filling installer…';
    try {
      // Expose full settings before interacting with dependent fields.
      const full = $('show_full_form');
      if (full && getComputedStyle(full).display !== 'none') {
        full.click();
        await sleep(500);
      }

      const options = [...$('softbranch').options].filter(o => !o.disabled && /^\s*\d+\.\d+/.test(o.textContent));
      options.sort((a, b) => compareVersion(b.textContent.trim(), a.textContent.trim()));
      if (!options.length) throw new Error('No WordPress versions found in dropdown.');
      const version = options[0].value;
      setField('softbranch', version);
      // selectversion() can reload controls asynchronously.
      await sleep(WAIT_AFTER_VERSION_MS);

      const domainOption = [...$('softdomain').options].find(o => !o.disabled && o.value);
      if (!domainOption) throw new Error('No installation domains available.');
      const domain = domainOption.value;
      setField('softdomain', domain);
      await sleep(WAIT_AFTER_DOMAIN_MS);

      const https = [...$('softproto').options].find(o => o.textContent.trim().toLowerCase() === 'https://' || o.value === '3');
      if (!https) throw new Error('HTTPS option not found.');
      const protocol = https.value;
      setField('softproto', protocol);
      await sleep(350);

      const name = humanise(domain);
      // Fill all non-dependent fields LAST so prior Softaculous AJAX actions cannot overwrite them.
      setField('softdirectory', '', false);
      setField('site_name', name, false);
      setField('site_desc', domain, false);
      setCheck('multisite', true);
      setCheck('disable_wp_cron', true);
      setField('admin_username', ADMIN_USERNAME, false);
      setField('admin_email', ADMIN_EMAIL, false);
      setField('admin_pass', password, false);
      // Password strength may rely on keyup; no secrets are logged/stored.
      dispatch($('admin_pass'), 'keyup');
      if (typeof window.check_pass_strength === 'function') window.check_pass_strength();
      if (typeof window.update_ins_url === 'function') window.update_ins_url();
      removePluginSelections();
      await sleep(400);

      let checks = verify({ version, domain, protocol, name, password });
      const failed = checks.filter(([, ok]) => !ok).map(([name]) => name);
      const success = failed.length === 0;
      button.style.backgroundColor = success ? '#166534' : '#b45309';
      alert((success ? 'All 12 checks passed.' : 'Some fields did NOT persist: ' + failed.join(', ')) +
        '\n\nTarget: https://' + domain + '/' +
        '\n\nReview all fields and the plugin selections before clicking Install. The script has NOT submitted the form.');
    } catch (err) {
      console.error('[RUP Softaculous autofill]', err);
      alert('Autofill stopped: ' + err.message + '\nOpen browser DevTools Console for details.');
    } finally {
      button.disabled = false;
      button.textContent = 'Fill WordPress Install';
    }
  }

  function mount() {
    if ($('rup-softaculous-fill') || !$('softbranch') || !$('softdomain') || !$('site_name')) return;
    const button = document.createElement('button');
    button.id = 'rup-softaculous-fill';
    button.type = 'button';
    button.textContent = 'Fill WordPress Install';
    button.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:2147483647;padding:12px 18px;background:#166534;color:white;border:0;border-radius:8px;box-shadow:0 4px 18px #0004;cursor:pointer;font-weight:700;';
    button.addEventListener('click', () => run(button));
    document.body.appendChild(button);
  }
  mount();
  new MutationObserver(mount).observe(document.documentElement, { childList: true, subtree: true });
})();
