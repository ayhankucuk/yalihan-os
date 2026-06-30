document.addEventListener('DOMContentLoaded', function () {
  var meta = document.querySelector('meta[name="csrf-token"]')
  var token = meta ? meta.getAttribute('content') : ''
  if (!token) return
  document.addEventListener('submit', function (e) {
    var form = e.target && e.target.closest ? e.target.closest('form') : null
    if (!form) return
    if (!form.querySelector('input[name="_token"]')) {
      var input = document.createElement('input')
      input.type = 'hidden'
      input.name = '_token'
      input.value = token
      form.appendChild(input)
    }
  }, true)
  var originalFetch = window.fetch
  if (typeof originalFetch === 'function') {
    window.fetch = function (input, init) {
      var opts = init || {}
      var headers = new Headers(opts.headers || {})
      headers.set('X-CSRF-TOKEN', token)
      return originalFetch(input, Object.assign({}, opts, { headers: headers }))
    }
  }
  var OldXHR = window.XMLHttpRequest
  if (typeof OldXHR === 'function') {
    function NewXHR() {
      var xhr = new OldXHR()
      var _open = xhr.open
      xhr.open = function () {
        _open.apply(xhr, arguments)
        try { xhr.setRequestHeader('X-CSRF-TOKEN', token) } catch (_) {}
      }
      xhr.addEventListener('readystatechange', function () {
        if (xhr.readyState === 4 && xhr.status === 403) {
          if (window.confirm('Oturum süresi dolmuş olabilir. Sayfayı yenilemek ister misiniz?')) {
            window.location.reload()
          }
        }
      })
      return xhr
    }
    window.XMLHttpRequest = NewXHR
  }
})
