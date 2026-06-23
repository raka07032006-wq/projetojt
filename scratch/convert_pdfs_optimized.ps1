function Convert-PdfToText($pdfPath, $txtPath) {
    $word = $null
    try {
        $word = New-Object -ComObject Word.Application
        $word.Visible = $false
        $word.DisplayAlerts = 0
        
        $doc = $word.Documents.Open($pdfPath, $false, $true)
        $doc.SaveAs($txtPath, 2) # wdFormatText = 2
        $doc.Close()
        $word.Quit()
        return $true
    } catch {
        Write-Output "  Error: $_"
        if ($word -ne $null) {
            try { $word.Quit() } catch {}
        }
        return $false
    }
}

$files = Get-ChildItem "c:\xampp\htdocs\ProjectsOJT\Data April\*.pdf"
Write-Output "Found $($files.Count) PDF files to process."

foreach ($file in $files) {
    $txtPath = $file.FullName -replace '\.pdf$', '.txt'
    if (Test-Path $txtPath) {
        Write-Output "$($file.Name) already has a converted text file. Skipping."
        continue
    }
    
    Write-Output "Converting: $($file.Name)..."
    $success = Convert-PdfToText $file.FullName $txtPath
    if ($success) {
        Write-Output "Successfully saved text to $($file.BaseName).txt"
    } else {
        Write-Output "Failed to convert $($file.Name)"
    }
}

Write-Output "All conversions done!"
