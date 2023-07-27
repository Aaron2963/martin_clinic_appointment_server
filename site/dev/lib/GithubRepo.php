<?php

namespace MISA\App;

class GithubRepo
{
    protected $token = '';
    public $owner = '';
    public $repo = '';
    public $branch = 'main';

    public function __construct($owner, $repo, $token = '', $branch = 'main')
    {
        $this->owner = $owner;
        $this->repo = $repo;
        $this->token = $token;
        $this->branch = $branch;
    }
    
    /**
     * Clone repository to destination
     *
     * @param  string $destination  The destination directory
     * @return true
     */
    public function clone($destination)
    {
        $public  = empty($this->token);
        if ($public) {
            $host = 'https://github.com';
        } else {
            $host = (strpos($this->token, 'ghp_') === 0 ? 'https://' : 'https://oauth2:') . $this->token . '@github.com';
        }
        $cmd = "git clone $host/{$this->owner}/{$this->repo}.git --branch {$this->branch} $destination";
        echo 'Cloning ' . $this->owner . '/' . $this->repo . PHP_EOL;
        exec($cmd);
        return true;
    }
    
    /**
     * Download directory from repository and return an array of filename => content.
     * If destination is not empty, the files will be saved to destination.
     *
     * @param  string $path         The path to the directory
     * @param  string $destination  The destination directory
     * @return array|false         An array of filename => content, or false if failed
     */
    public function downloadDirectory($path, $destination = '')
    {
        $owner = $this->owner;
        $repo = $this->repo;
        $token = $this->token;
        $branch = $this->branch;
        $path = rtrim($path, '/');
        
        // Set the API endpoint
        $url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}?ref={$branch}";

        // Set the request headers
        $headers = [
            'User-Agent: php',
            "X-GitHub-Api-Version: 2022-11-28",
            'Accept: application/vnd.github.v3+json'
        ];
        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        // Make the API request
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));
        $result = curl_exec($curl);
        curl_close($curl);
        if (!$result) {
            echo "Error: No response from API" . PHP_EOL;
            echo curl_error($curl);
            return false;
        }

        // Decode the JSON response
        $data = json_decode($result, true);
        echo "Downloading " . count($data) . " files from GitHub $owner/$repo:$branch/$path/..." . PHP_EOL;

        // Iterate over the contents and download each file
        $files = [];
        foreach ($data as $item) {
            // Skip directories
            if ($item['type'] == 'dir') {
                continue;
            }

            // Set the file URL and download path
            $file_url = $item['download_url'];
            $file_path = $item['name'];

            // Download the file
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $file_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $file_content = curl_exec($ch);
            curl_close($ch);

            if (!empty($destination)) {
                if (!is_dir($destination)) {
                    mkdir($destination, 0777, true);
                }
                file_put_contents($destination . '/' . $file_path, $file_content);
            }

            $files[$file_path] = $file_content;
        }

        echo "Client Schema downloaded successfully!" . PHP_EOL;
        return $files;
    }
}
