{{-- Global Image Preview Modal --}}
<div id="imagePreviewModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-all duration-300">
    <div class="relative max-w-4xl w-full max-h-[90vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-image text-emerald-600"></i> Preview Bukti Lampiran
            </h3>
            <button type="button" class="close-image-modal-btn w-9 h-9 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-xl flex items-center justify-center text-gray-700 dark:text-gray-300 transition-colors">
                <i class="fas fa-times text-base"></i>
            </button>
        </div>
        <div class="p-6 overflow-auto flex items-center justify-center bg-gray-900/5 dark:bg-gray-950/50 flex-1">
            <img id="modalImagePreview" src="" alt="Bukti Lampiran" class="max-h-[70vh] max-w-full object-contain rounded-xl shadow-md">
        </div>
        <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-3">
            <a id="modalImageDownload" href="" target="_blank" download class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition-all">
                <i class="fas fa-external-link-alt"></i> Buka di Tab Baru / Download
            </a>
            <button type="button" class="close-image-modal-btn px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-semibold transition-all">
                Tutup
            </button>
        </div>
    </div>
</div>

<script nonce="{{ config('app.csp_nonce') }}">
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('imagePreviewModal');
    const img = document.getElementById('modalImagePreview');
    const downloadBtn = document.getElementById('modalImageDownload');

    function openImageModal(imageUrl) {
        if (modal && img) {
            img.src = imageUrl;
            if (downloadBtn) downloadBtn.href = imageUrl;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeImageModal() {
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (img) img.src = '';
        }
    }

    document.querySelectorAll('.preview-image-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.dataset.imageUrl;
            if (url) {
                openImageModal(url);
            }
        });
    });

    document.querySelectorAll('.close-image-modal-btn').forEach(function(btn) {
        btn.addEventListener('click', closeImageModal);
    });

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
    }
});
</script>
