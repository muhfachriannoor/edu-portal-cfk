<div class="flex flex-col items-center text-center gap-2">

    <!-- 🔼 Download Sample Excel Button (Top) -->
    <a href="{{ asset($sampleFile) }}" 
        onclick="showDownloadLoading(this)"
        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
        <i class="fas fa-file-excel mr-2"></i>
        <span class="download-text">Download Sample Format</span>
        <svg class="hidden download-spinner animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
    </a>

    <!-- 📝 Explanation -->
    <p class="text-sm text-gray-700">
        Please download the sample format before uploading your file.
    </p>

    <!-- 🆗 Spacer (Optional) -->
    <hr class="w-full border-t my-2" />

    <!-- ⬇️ Upload File Button (Bottom) -->
    <button onclick="document.getElementById('importFileInput').click()" 
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700">
        <i class="fas fa-upload mr-2"></i> Choose File
    </button>

    <input type="file" id="importFileInput" class="hidden mt-2" accept=".csv,.xls,.xlsx"
        onchange="document.getElementById('swalFileName').innerText = this.files[0]?.name || ''">

    <p id="swalFileName" class="mt-2 text-sm text-gray-700"></p>
</div>