<template x-if="$data.portal_ids">
    <div class="contents">
        <div>
            <label class="block text-sm">Sahibinden No</label>
            <input type="text" name="sahibinden_id" x-model="portal_ids.sahibinden"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100"
                placeholder="Örn: 123456789" />
        </div>
        <div>
            <label class="block text-sm">Emlakjet No</label>
            <input type="text" name="emlakjet_id" x-model="portal_ids.emlakjet"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100"
                placeholder="Örn: EJ-12345" />
        </div>
        <div>
            <label class="block text-sm">Hepsiemlak No</label>
            <input type="text" name="hepsiemlak_id" x-model="portal_ids.hepsiemlak"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100"
                placeholder="Örn: 55555-1234" />
        </div>
        <div>
            <label class="block text-sm">Zingat No</label>
            <input type="text" name="zingat_id" x-model="portal_ids.zingat"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100"
                placeholder="Örn: 4444444" />
        </div>
        <div>
            <label class="block text-sm">Hürriyet Emlak No</label>
            <input type="text" name="hurriyetemlak_id" x-model="portal_ids.hurriyetemlak"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:bg-slate-900 dark:text-slate-100"
                placeholder="Örn: 987654321" />
        </div>
    </div>
</template>
