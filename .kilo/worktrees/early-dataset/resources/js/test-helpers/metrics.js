import fs from 'fs';
import path from 'path';

export function createCollector(name = 'list-paginate') {
    const store = { render: [], paginate: [], total: [] };
    function onMetrics({ name: n, ms }) {
        if (store[n]) store[n].push(ms);
    }
    async function flush() {
        const now = new Date();
        const ym = String(now.getFullYear()) + '-' + String(now.getMonth() + 1).padStart(2, '0');
        const dir = path.join(process.cwd(), 'yalihan-bekci', 'reports', ym);
        const file = path.join(dir, name + '-summary.txt');
        try {
            fs.mkdirSync(dir, { recursive: true });
        } catch {}
        const avg = (arr) =>
            arr.length ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length) : 0;
        const lines = [
            'render: ' + avg(store.render) + ' ms',
            'paginate: ' + avg(store.paginate) + ' ms',
            'total: ' + avg(store.total) + ' ms',
        ];
        try {
            fs.writeFileSync(file, lines.join('\n'), 'utf8');
        } catch {}
    }
    return { onMetrics, flush };
}
