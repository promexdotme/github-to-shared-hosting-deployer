<?php
/**
 * GitHub to Shared Hosting Deployer
 * A tool to deploy GitHub repos to cPanel/Shared Hosting without SSH.
 *
 * https://github.com/promexdotme/github-to-shared-hosting-deployer
 */
// Increase time limit for large downloads
set_time_limit(300);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['github_url'])) {
    $url = $_POST['github_url'];
    $token = isset($_POST['auth_token']) ? $_POST['auth_token'] : '';
    
    // 1. Parse the URL to get details
    $parsed = parseGithubUrl($url);
    
    if ($parsed) {
        $zipUrl = "https://github.com/{$parsed['user']}/{$parsed['repo']}/archive/{$parsed['branch']}.zip";
        $tempZip = 'temp_repo_' . time() . '.zip';
        
        // 2. Download the ZIP (using CURL to handle redirects and User-Agent)
        $fp = fopen($tempZip, 'w+');
        $ch = curl_init($zipUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (PHP-Deploy-Script)');
        
        // Add auth token if provided (for private repos)
        if (!empty($token)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: token $token"]);
        }

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode == 200 && file_exists($tempZip)) {
            // 3. Unzip and extract specific folder or whole repo
            $zip = new ZipArchive;
            if ($zip->open($tempZip) === TRUE) {
                
                // GitHub zips always contain a root folder like "repo-branch/"
                // We need to figure out what that folder name is dynamically
                $rootName = $zip->getNameIndex(0); 
                // Usually "User-Repo-Hash/" or "Repo-Branch/"
                // We will rely on string detection
                
                $targetPath = $parsed['path']; // The folder the user wants (e.g., "dist")
                $extractionCount = 0;

                for($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    
                    // Determine if this file is within our target path
                    // The entryName is like: "project-main/dist/css/style.css"
                    // We want to check if it starts with: "project-main/dist/"
                    
                    // Get the top-level directory length
                    $slashPos = strpos($entryName, '/');
                    if ($slashPos === false) continue;
                    
                    $rootPrefix = substr($entryName, 0, $slashPos + 1); // "project-main/"
                    
                    // Construct the full search path inside the zip
                    $searchPath = $rootPrefix . ($targetPath ? $targetPath . '/' : '');
                    
                    // Check if file matches the path we want
                    if (strpos($entryName, $searchPath) === 0) {
                        // It's a match!
                        // Remove the full prefix to Flatten the structure
                        // e.g. "project-main/dist/css/style.css" -> "css/style.css"
                        $localName = substr($entryName, strlen($searchPath));
                        
                        // Skip if it is just a directory entry
                        if (empty($localName)) continue;
                        if (substr($entryName, -1) == '/') continue;

                        // Ensure local directory exists
                        $dir = dirname($localName);
                        if (!is_dir($dir) && $dir != '.') {
                             mkdir($dir, 0755, true);
                        }
                        
                        // Write the file
                        copy("zip://" . $tempZip . "#" . $entryName, "./" . $localName);
                        $extractionCount++;
                    }
                }
                
                $zip->close();
                $message = "Success! Extracted $extractionCount files.";
            } else {
                $error = "Failed to open the downloaded ZIP file.";
            }
            
            // Cleanup
            @unlink($tempZip);
            
        } else {
            $error = "Failed to download from GitHub. HTTP Code: $httpCode. (Check if repo is public or branch name is correct).";
            @unlink($tempZip);
        }
        
    } else {
        $error = "Invalid GitHub URL. Please use standard format.";
    }
}

function parseGithubUrl($url) {
    // Regex to handle standard URLs and subfolder paths
    // Supports: https://github.com/user/repo
    // Supports: https://github.com/user/repo/tree/branch/folder
    $pattern = '#github\.com/([^/]+)/([^/]+)(?:/tree/([^/]+)(?:/(.*))?)?#';
    
    if (preg_match($pattern, $url, $matches)) {
        return [
            'user' => $matches[1],
            'repo' => $matches[2],
            'branch' => !empty($matches[3]) ? $matches[3] : 'main', // Default to main
            'path' => !empty($matches[4]) ? $matches[4] : ''
        ];
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Git Pull (No Git)</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f4f6f8; display: flex; justify-content: center; padding-top: 50px; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 400px; }
        h2 { margin-top: 0; color: #333; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #2ea44f; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        button:hover { background: #2c974b; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 14px; }
        .success { background: #d0f0c0; color: #1e561e; }
        .error { background: #f8d7da; color: #721c24; }
        .note { font-size: 12px; color: #666; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Deploy from GitHub</h2>
    
    <?php if($message): ?>
        <div class="msg success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="msg error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label><strong>GitHub URL</strong></label>
        <input type="text" name="github_url" placeholder="e.g. https://github.com/user/repo/tree/main/dist" required>
        
        <label><strong>Personal Access Token</strong> (Optional)</label>
        <input type="password" name="auth_token" placeholder="Only for private repos">

        <button type="submit">Fetch & Deploy</button>
    </form>
    
    <div class="note">
        <strong>Note:</strong> This will overwrite files in the current folder. <br>
        If a subfolder is specified in the URL (e.g. <code>/dist</code>), only files inside it are extracted to the root here.
    </div>
</div>

</body>
</html>
