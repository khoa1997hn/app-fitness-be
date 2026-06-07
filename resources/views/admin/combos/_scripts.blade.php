<script>
    const maxInfos = 3;
    let infoIndex = document.querySelectorAll('.info-row').length;

    function reindexInfoRows() {
        document.querySelectorAll('.info-row').forEach((row, idx) => {
            row.dataset.index = idx;
            row.querySelector('.info-number').textContent = idx + 1;

            row.querySelectorAll('[data-index]').forEach(el => {
                el.dataset.index = idx;
            });

            row.querySelectorAll('[name^="infos["]').forEach(input => {
                input.name = input.name.replace(/infos\[\d+\]/, 'infos[' + idx + ']');
            });
        });

        infoIndex = document.querySelectorAll('.info-row').length;
        document.getElementById('add-info-btn').disabled = infoIndex >= maxInfos;
    }

    document.getElementById('add-info-btn')?.addEventListener('click', function () {
        if (infoIndex >= maxInfos) return;

        const template = document.getElementById('info-row-template').innerHTML;
        const html = template.replace(/__INDEX__/g, infoIndex);
        document.getElementById('infos-container').insertAdjacentHTML('beforeend', html);
        infoIndex++;
        reindexInfoRows();
        bindInfoIconInputs();
        bindRemoveButtons();
    });

    function bindRemoveButtons() {
        document.querySelectorAll('.remove-info-btn').forEach(btn => {
            btn.onclick = function () {
                this.closest('.info-row').remove();
                reindexInfoRows();
            };
        });
    }

    document.querySelectorAll('.combo-cover-input').forEach(function (input) {
        input.addEventListener('change', async function () {
            if (!this.files.length) return;

            const locale = this.dataset.locale;
            const status = document.querySelector('.combo-cover-status[data-locale="' + locale + '"]');
            status.textContent = 'Đang tải lên...';

            try {
                const meta = await window.AdminS3Upload.upload(this.files[0], 'combo_cover');
                document.querySelector('.combo-cover-path[data-locale="' + locale + '"]').value = meta.path;
                document.querySelector('.combo-cover-name[data-locale="' + locale + '"]').value = meta.name;
                document.querySelector('.combo-cover-extension[data-locale="' + locale + '"]').value = meta.extension || '';
                document.querySelector('.combo-cover-size[data-locale="' + locale + '"]').value = meta.size || '';
                const preview = document.querySelector('.combo-cover-preview[data-locale="' + locale + '"]');
                if (preview) {
                    preview.src = URL.createObjectURL(this.files[0]);
                    preview.classList.remove('hidden');
                }
                status.textContent = 'Đã tải lên: ' + meta.name;
            } catch (error) {
                status.textContent = 'Lỗi: ' + error.message;
            }
        });
    });

    function bindInfoIconInputs() {
        document.querySelectorAll('.combo-info-icon-input').forEach(function (input) {
            if (input.dataset.bound) return;
            input.dataset.bound = '1';

            input.addEventListener('change', async function () {
                if (!this.files.length) return;

                const index = this.dataset.index;
                const status = document.querySelector('.combo-info-icon-status[data-index="' + index + '"]');
                status.textContent = 'Đang tải lên...';

                try {
                    const meta = await window.AdminS3Upload.upload(this.files[0], 'combo_info_icon');
                    document.querySelector('.combo-info-icon-path[data-index="' + index + '"]').value = meta.path;
                    document.querySelector('.combo-info-icon-name[data-index="' + index + '"]').value = meta.name;
                    document.querySelector('.combo-info-icon-extension[data-index="' + index + '"]').value = meta.extension || '';
                    document.querySelector('.combo-info-icon-size[data-index="' + index + '"]').value = meta.size || '';
                    const preview = document.querySelector('.combo-info-icon-preview[data-index="' + index + '"]');
                    if (preview) {
                        preview.src = URL.createObjectURL(this.files[0]);
                        preview.classList.remove('hidden');
                    }
                    status.textContent = 'Đã tải lên: ' + meta.name;
                } catch (error) {
                    status.textContent = 'Lỗi: ' + error.message;
                }
            });
        });
    }

    bindInfoIconInputs();
    bindRemoveButtons();
</script>
