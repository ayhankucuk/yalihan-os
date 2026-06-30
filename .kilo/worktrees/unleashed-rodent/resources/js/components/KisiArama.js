$('#kisiArama').select2({
    ajax: {
        url: (window.APIConfig && window.APIConfig.liveSearch && window.APIConfig.liveSearch.kisiler)
            ? window.APIConfig.liveSearch.kisiler
            : '/api/v1/kisiler/search',
        dataType: 'json',
        delay: 500,
        data: function (params) {
            return { q: params.term, limit: 10 };
        },
        processResults: function (data) {
            let items = [];
            if (Array.isArray(data)) {
                items = data;
            } else if (data && data.data) {
                items = Array.isArray(data.data) ? data.data : (data.data.data || []);
            }
            const results = items.map(item => ({ id: item.id, text: item.tam_ad || item.name || item.display_text || `#${item.id}` }));
            return { results };
        },
    },
    minimumInputLength: 2,
    placeholder: 'Kişi ara...',
    allowClear: true,
});
