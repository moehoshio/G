/**
 * G Theme - Content Translation Module
 *
 * Provides client-side Simplified/Traditional Chinese conversion with:
 * - Language selector UI (Simplified, Traditional, Original)
 * - Auto-detection of user language on first visit
 * - localStorage persistence of preference
 * - Pure DOM-based conversion that preserves HTML structure
 *
 * Notes:
 * - Code blocks (<pre>, <code>), <script>, <style>, <textarea>, <noscript>
 *   are skipped so syntax-highlighted code, math source, and embedded scripts
 *   are never mutated.
 * - Only text-node values are converted; element tag names, attributes
 *   (e.g. <a href>, class names, inline styles) and the document tree
 *   structure (tables, lists, etc.) are left untouched.
 * - Conversion always starts from the cached original HTML, so repeated
 *   switching between languages is idempotent and round-trips cleanly:
 *   for any rendered language L the user sees a deterministic projection
 *   of the original content rather than a chain of conversions.
 */

;(function () {
  'use strict'

  const STORAGE_KEY_LANG = 'g_content_lang'
  const ORIGINAL_VALUE = '__original__'
  const SUPPORTED_LANGS = {
    'zh-CN': '简体中文',
    'zh-TW': '繁體中文',
  }

  // Tag names whose text content must never be converted.
  const SKIP_TAGS = {
    SCRIPT: 1, STYLE: 1, PRE: 1, CODE: 1,
    TEXTAREA: 1, NOSCRIPT: 1, KBD: 1, SAMP: 1, VAR: 1,
  }

  let originalContent = ''
  let currentLang = ''
  let sourceLang = ''

  // Lazily-built code-point maps.
  let stMap = null   // Simplified -> Traditional
  let tsMap = null   // Traditional -> Simplified

  /**
   * Build a Map<string,string> from a packed "k1v1k2v2..." string.
   * Uses Array.from to be code-point aware (handles surrogate pairs).
   */
  function buildMap(packed) {
    const arr = Array.from(packed)
    const m = new Map()
    for (let i = 0; i + 1 < arr.length; i += 2) {
      m.set(arr[i], arr[i + 1])
    }
    return m
  }

  function ensureMaps() {
    if (stMap && tsMap) return true
    const data = window.ZHCONV_DATA
    if (!data || !data.st || !data.ts) return false
    if (!stMap) stMap = buildMap(data.st)
    if (!tsMap) tsMap = buildMap(data.ts)
    return true
  }

  /**
   * Convert a single text string using the given code-point map.
   */
  function convertText(text, map) {
    if (!text) return text
    let out = ''
    // for..of iterates by Unicode code point, so surrogate pairs stay intact.
    for (const ch of text) {
      const r = map.get(ch)
      out += r !== undefined ? r : ch
    }
    return out
  }

  /**
   * Walk the DOM subtree and convert every text node in place.
   * Skips elements listed in SKIP_TAGS, so code blocks, scripts, styles,
   * textareas, etc. are preserved verbatim.
   */
  function convertSubtree(root, map) {
    // TreeWalker would also work, but a manual walk lets us skip whole subtrees
    // (including their descendants) by simply not recursing into them.
    const stack = [root]
    while (stack.length) {
      const node = stack.pop()
      if (!node) continue
      const type = node.nodeType
      if (type === 3 /* TEXT_NODE */) {
        const v = node.nodeValue
        if (v) {
          const conv = convertText(v, map)
          if (conv !== v) node.nodeValue = conv
        }
        continue
      }
      if (type !== 1 /* ELEMENT_NODE */) continue
      if (SKIP_TAGS[node.tagName]) continue
      for (let c = node.firstChild; c; c = c.nextSibling) {
        stack.push(c)
      }
    }
  }

  /**
   * Show notification message (uses showToast if available, falls back to console)
   */
  function notify(msg) {
    try {
      if (typeof showToast === 'function') {
        showToast(msg)
      } else if (window.showToast && typeof window.showToast === 'function') {
        window.showToast(msg)
      }
    } catch (e) {
      // silent fallback
    }
  }

  /**
   * Detect user's preferred language from browser
   */
  function detectBrowserLang() {
    const nav = navigator.language || navigator.userLanguage || 'zh-CN'
    const lower = nav.toLowerCase()
    if (lower.startsWith('zh')) {
      if (lower.includes('tw') || lower.includes('hk') ||
          lower.includes('mo') || lower.includes('hant')) {
        return 'zh-TW'
      }
      return 'zh-CN'
    }
    // Non-Chinese browsers: keep the document's source language so the
    // page is not converted unexpectedly.
    return ORIGINAL_VALUE
  }

  function getSavedLang() {
    try { return localStorage.getItem(STORAGE_KEY_LANG) } catch (e) { return null }
  }

  function saveLang(lang) {
    try { localStorage.setItem(STORAGE_KEY_LANG, lang) } catch (e) {}
  }

  /**
   * Detect source language of the page content from the <html lang> attribute.
   */
  function detectSourceLang() {
    const htmlLang = (document.documentElement.lang || '').toLowerCase()
    if (htmlLang.indexOf('hant') !== -1 || htmlLang.indexOf('tw') !== -1 ||
        htmlLang.indexOf('hk') !== -1 || htmlLang.indexOf('mo') !== -1) {
      return 'zh-TW'
    }
    return 'zh-CN'
  }

  /**
   * Get label for "Show Original" option based on source language
   */
  function getOriginalLabel() {
    return sourceLang === 'zh-TW' ? '顯示原文' : '显示原文'
  }

  /**
   * Apply the target language to the content element.
   * Always rebuilds from the cached original HTML so the operation is
   * idempotent and stable across repeated invocations.
   */
  function applyLang(targetLang) {
    const contentEl = document.querySelector('.post-content.PAP-content, #page-content.PAP-content')
    if (!contentEl) return

    // Always restore the original first; this guarantees that switching
    // CN -> TW -> CN -> TW ... never drifts and that "show original" works.
    contentEl.innerHTML = originalContent

    if (targetLang !== ORIGINAL_VALUE &&
        SUPPORTED_LANGS[targetLang] &&
        targetLang !== sourceLang) {
      if (!ensureMaps()) {
        notify('Conversion data not loaded')
        reinitContent()
        return
      }
      const map = (targetLang === 'zh-CN') ? tsMap : stMap
      convertSubtree(contentEl, map)
    }

    reinitContent()
  }

  /**
   * Re-initialize content plugins after content was replaced.
   */
  function reinitContent() {
    if (typeof Prism !== 'undefined') {
      Prism.highlightAll(true, null)
    }
    if (window.G_CONFIG && window.G_CONFIG.katex && typeof renderMathInElement === 'function') {
      const contentEl = document.querySelector('.post-content.PAP-content, #page-content.PAP-content')
      if (contentEl) {
        renderMathInElement(contentEl, {
          delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '$', right: '$', display: false },
          ],
          throwOnError: false,
        })
      }
    }
  }

  /**
   * Create the language selector UI
   */
  function createSelector(container) {
    const wrapper = document.createElement('div')
    wrapper.id = 'content-lang-selector'
    wrapper.className = 'content-lang-selector'

    const label = document.createElement('span')
    label.className = 'content-lang-label'
    label.textContent = '🌐'

    const select = document.createElement('select')
    select.id = 'content-lang-select'
    select.className = 'content-lang-select'

    const origOption = document.createElement('option')
    origOption.value = ORIGINAL_VALUE
    origOption.textContent = '📄 ' + getOriginalLabel()
    select.appendChild(origOption)

    Object.keys(SUPPORTED_LANGS).forEach(function (lang) {
      const option = document.createElement('option')
      option.value = lang
      option.textContent = SUPPORTED_LANGS[lang]
      select.appendChild(option)
    })

    select.value = currentLang

    select.addEventListener('change', function () {
      const targetLang = this.value
      currentLang = targetLang
      saveLang(targetLang)
      applyLang(targetLang)
    })

    wrapper.appendChild(label)
    wrapper.appendChild(select)
    container.insertBefore(wrapper, container.firstChild)
  }

  /**
   * Initialize content translation
   */
  function initTranslation() {
    const contentEl = document.querySelector('.post-content.PAP-content, #page-content.PAP-content')
    if (!contentEl) return

    // Remove existing selector (e.g. on pjax re-init)
    const existingSelector = document.getElementById('content-lang-selector')
    if (existingSelector) existingSelector.remove()

    originalContent = contentEl.innerHTML
    sourceLang = detectSourceLang()

    const savedLang = getSavedLang()
    if (savedLang === ORIGINAL_VALUE || SUPPORTED_LANGS[savedLang]) {
      currentLang = savedLang
    } else {
      currentLang = detectBrowserLang()
      saveLang(currentLang)
    }

    const articleEl = contentEl.parentElement
    if (articleEl) createSelector(articleEl)

    // Apply conversion if needed (no network call required).
    if (currentLang !== ORIGINAL_VALUE &&
        SUPPORTED_LANGS[currentLang] &&
        currentLang !== sourceLang) {
      applyLang(currentLang)
    }
  }

  // Expose for pjax re-initialization (called by pageInit)
  window.initContentTranslation = initTranslation
})()
