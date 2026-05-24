/**
 * G Theme - Content Translation Module
 * 
 * Provides client-side content translation with:
 * - Language selector UI
 * - Auto-detection of user language on first visit
 * - localStorage caching of translated content and preference
 * - MyMemory API via server-side proxy
 */

;(function () {
  'use strict'

  const STORAGE_KEY_LANG = 'g_content_lang'
  const STORAGE_KEY_CACHE = 'g_translate_cache'
  const SUPPORTED_LANGS = {
    'zh-CN': '简体中文',
    'zh-TW': '繁體中文',
    'en': 'English',
  }

  let originalContent = ''
  let currentLang = ''
  let sourceLang = ''
  let translating = false

  /**
   * Self-contained POST request helper (no external dependencies)
   */
  function postRequest(url, data) {
    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest()
      xhr.open('POST', url, true)
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
      xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
          if (xhr.status === 200 || xhr.status === 304) {
            resolve(xhr.responseText)
          } else {
            reject(xhr.responseText)
          }
        }
      }
      xhr.onerror = function () {
        reject('Network error')
      }
      xhr.send(data)
    })
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
      if (lower.includes('tw') || lower.includes('hk') || lower.includes('hant')) {
        return 'zh-TW'
      }
      return 'zh-CN'
    }
    if (lower.startsWith('en')) {
      return 'en'
    }
    // Default to English for other languages
    return 'en'
  }

  /**
   * Get saved language preference
   */
  function getSavedLang() {
    try {
      return localStorage.getItem(STORAGE_KEY_LANG)
    } catch (e) {
      return null
    }
  }

  /**
   * Save language preference
   */
  function saveLang(lang) {
    try {
      localStorage.setItem(STORAGE_KEY_LANG, lang)
    } catch (e) {}
  }

  /**
   * Get cached translation for content hash + target lang
   */
  function getCachedTranslation(hash, targetLang) {
    try {
      const cache = JSON.parse(localStorage.getItem(STORAGE_KEY_CACHE) || '{}')
      const key = hash + '_' + targetLang
      if (cache[key] && cache[key].time > Date.now() - 86400000) {
        return cache[key].text
      }
    } catch (e) {}
    return null
  }

  /**
   * Save translation to cache
   */
  function setCachedTranslation(hash, targetLang, text) {
    try {
      const cache = JSON.parse(localStorage.getItem(STORAGE_KEY_CACHE) || '{}')
      const key = hash + '_' + targetLang
      // Keep cache size reasonable - max 20 entries
      const keys = Object.keys(cache)
      if (keys.length > 20) {
        // Remove oldest entries
        keys.sort((a, b) => cache[a].time - cache[b].time)
        for (let i = 0; i < keys.length - 15; i++) {
          delete cache[keys[i]]
        }
      }
      cache[key] = { text: text, time: Date.now() }
      localStorage.setItem(STORAGE_KEY_CACHE, JSON.stringify(cache))
    } catch (e) {}
  }

  /**
   * Simple hash function for content
   */
  function hashContent(text) {
    let hash = 0
    const str = text.substring(0, 200) // Use first 200 chars for hash
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i)
      hash = ((hash << 5) - hash) + char
      hash = hash & hash // Convert to 32bit integer
    }
    return Math.abs(hash).toString(36)
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

    // Add "Show Original" option first
    const origOption = document.createElement('option')
    origOption.value = '__original__'
    origOption.textContent = '📄 ' + getOriginalLabel()
    select.appendChild(origOption)

    // Add all language options
    Object.keys(SUPPORTED_LANGS).forEach(function (lang) {
      const option = document.createElement('option')
      option.value = lang
      option.textContent = SUPPORTED_LANGS[lang]
      select.appendChild(option)
    })

    // Set current selection
    select.value = currentLang

    // Loading indicator
    const loading = document.createElement('span')
    loading.id = 'content-lang-loading'
    loading.className = 'content-lang-loading'
    loading.style.display = 'none'
    loading.textContent = '⏳'

    select.addEventListener('change', function () {
      const targetLang = this.value
      saveLang(targetLang)
      translateContent(targetLang, loading)
    })

    wrapper.appendChild(label)
    wrapper.appendChild(select)
    wrapper.appendChild(loading)

    container.insertBefore(wrapper, container.firstChild)
  }

  /**
   * Get label for "Show Original" option based on source language
   */
  function getOriginalLabel() {
    var labels = {
      'zh-CN': '显示原文',
      'zh-TW': '顯示原文',
      'en': 'Original',
    }
    return labels[sourceLang] || '原文'
  }

  /**
   * Translate content to target language
   */
  function translateContent(targetLang, loadingEl) {
    if (translating) return
    currentLang = targetLang

    var contentEl = document.querySelector('.post-content.PAP-content, #page-content.PAP-content')
    if (!contentEl) return

    // If target is "show original" or same as source language, restore original
    if (targetLang === '__original__' || targetLang === sourceLang) {
      contentEl.innerHTML = originalContent
      reinitContent()
      return
    }

    // Check cache
    var hash = hashContent(originalContent)
    var cached = getCachedTranslation(hash, targetLang)
    if (cached) {
      contentEl.innerHTML = cached
      reinitContent()
      return
    }

    // Call translation API
    translating = true
    if (loadingEl) loadingEl.style.display = 'inline'

    var textContent = contentEl.innerHTML
    var url = window.G_CONFIG.themeUrl + 'libs/translate.php'
    var body = 'text=' + encodeURIComponent(textContent) +
      '&source=' + encodeURIComponent(sourceLang) +
      '&target=' + encodeURIComponent(targetLang)

    try {
      postRequest(url, body).then(function (response) {
        translating = false
        if (loadingEl) loadingEl.style.display = 'none'

        try {
          var data = JSON.parse(response)
          if (data.translated) {
            contentEl.innerHTML = data.translated
            setCachedTranslation(hash, targetLang, data.translated)
            reinitContent()
          } else if (data.error) {
            notify(data.error)
          }
        } catch (e) {
          notify('Translation failed')
        }
      }).catch(function () {
        translating = false
        if (loadingEl) loadingEl.style.display = 'none'
        notify('Translation request failed')
      })
    } catch (e) {
      translating = false
      if (loadingEl) loadingEl.style.display = 'none'
      notify('Translation request failed')
    }
  }

  /**
   * Re-initialize content plugins after translation
   */
  function reinitContent() {
    // Re-init syntax highlighting
    if (typeof Prism !== 'undefined') {
      Prism.highlightAll(true, null)
    }
    // Re-init KaTeX if enabled
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
   * Detect source language of the page content
   */
  function detectSourceLang() {
    // Use the page's HTML lang attribute or default
    const htmlLang = document.documentElement.lang || 'zh-Hans'
    if (htmlLang.includes('Hant') || htmlLang.includes('TW') || htmlLang.includes('HK')) {
      return 'zh-TW'
    }
    if (htmlLang.includes('en')) {
      return 'en'
    }
    return 'zh-CN'
  }

  /**
   * Initialize content translation
   */
  function initTranslation() {
    const contentEl = document.querySelector('.post-content.PAP-content, #page-content.PAP-content')
    if (!contentEl) return

    // Remove existing selector if present (pjax re-init)
    const existingSelector = document.getElementById('content-lang-selector')
    if (existingSelector) {
      existingSelector.remove()
    }

    // Store original content
    originalContent = contentEl.innerHTML

    // Detect source language
    sourceLang = detectSourceLang()

    // Determine target language
    const savedLang = getSavedLang()
    if (savedLang && (savedLang === '__original__' || SUPPORTED_LANGS[savedLang])) {
      currentLang = savedLang
    } else {
      currentLang = detectBrowserLang()
      saveLang(currentLang)
    }

    // Create selector UI
    const articleEl = contentEl.parentElement
    if (articleEl) {
      createSelector(articleEl)
    }

    // Auto-translate if needed (not for original or source language)
    if (currentLang !== '__original__' && currentLang !== sourceLang) {
      const loadingEl = document.getElementById('content-lang-loading')
      translateContent(currentLang, loadingEl)
    }
  }

  // Expose for pjax re-initialization (called by pageInit)
  window.initContentTranslation = initTranslation
})()
