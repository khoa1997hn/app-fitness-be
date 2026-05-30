/**
 * Admin S3 presigned upload helper.
 *
 * Usage:
 *   const fileMeta = await AdminS3Upload.upload(input.files[0], 'program_cover');
 *   // fileMeta: { path, name, extension, size }
 */
(function (window) {
    'use strict';

    function getMeta(name) {
        const el = document.querySelector('meta[name="' + name + '"]');

        return el ? el.getAttribute('content') : null;
    }

    async function parseJsonResponse(response) {
        try {
            return await response.json();
        } catch (error) {
            throw new Error('Phản hồi không hợp lệ từ server');
        }
    }

    async function requestPresignedUpload(file, type) {
        const url = getMeta('admin-presigned-upload-url');

        if (!url) {
            throw new Error('Chưa cấu hình URL presigned upload');
        }

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getMeta('csrf-token') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                type: type,
                filename: file.name,
                mimetype: file.type,
                size: file.size,
            }),
        });

        const body = await parseJsonResponse(response);

        if (!response.ok || !body.success) {
            const detail = body.errors ? ' ' + JSON.stringify(body.errors) : '';

            throw new Error((body.message || 'Không thể lấy presigned URL') + detail);
        }

        return body.data;
    }

    async function putToS3(uploadUrl, file, headers) {
        const response = await fetch(uploadUrl, {
            method: 'PUT',
            headers: headers || { 'Content-Type': file.type },
            body: file,
        });

        if (!response.ok) {
            throw new Error('Upload lên S3 thất bại (HTTP ' + response.status + ')');
        }
    }

    /**
     * @param {File} file
     * @param {string} type FileType value (vd. program_cover)
     * @returns {Promise<{path: string, name: string, extension: string, size: number}>}
     */
    async function upload(file, type) {
        const presigned = await requestPresignedUpload(file, type);

        await putToS3(presigned.upload_url, file, presigned.headers);

        return presigned.file;
    }

    window.AdminS3Upload = {
        upload: upload,
        requestPresignedUpload: requestPresignedUpload,
        putToS3: putToS3,
    };
})(window);
