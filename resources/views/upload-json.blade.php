<!DOCTYPE html>
<html>
<head>
    <title>Upload JSON</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">

    <div class="w-full bg-white p-6 rounded-2xl shadow">
        <h1 class="text-xl font-bold mb-4">Upload JSON File</h1>

        {{-- Upload Form --}}
        <form action="/upload-json" method="POST" enctype="multipart/form-data" class="mb-4">
            @csrf
            <input type="file" name="json_file" accept=".json,.txt"
                   class="block w-full border rounded p-2 mb-2">
            @error('json_file')
                <p class="text-red-500 text-sm">{{ $message }}</p>
            @enderror
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Upload</button>
        </form>

        {{-- Display JSON --}}
        @if (!empty($prettyJson))
            <h2 class="text-lg font-semibold mt-4 mb-2">JSON Content:</h2>
            <pre class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto text-sm w-full">
{{ $prettyJson }}
            </pre>
        @endif
    </div>

</body>
</html>
