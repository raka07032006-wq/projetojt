$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0

$files = Get-ChildItem "c:\xampp\htdocs\ProjectsOJT\Data April\*.pdf"
Write-Output "Found $($files.Count) PDF files to process."

foreach ($file in $files) {
    Write-Output "Converting: $($file.Name)..."
    try {
        $doc = $word.Documents.Open($file.FullName, $false, $true)
        $txtPath = $file.FullName -replace '\.pdf$', '.txt'
        $doc.SaveAs($txtPath, 2) # 2 is wdFormatText
        $doc.Close()
        Write-Output "Successfully saved text to $($file.BaseName).txt"
    } catch {
        Write-Output "ERROR on $($file.Name): $_"
    }
}

$word.Quit()
Write-Output "Done!"
