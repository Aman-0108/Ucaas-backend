<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SFTP;

class SSHService
{
    protected $sftp;

    /**
     * Constructor for initializing an SFTP connection.
     *
     * This constructor creates a new SFTP connection using the provided host address,
     * username, and password. It initializes the SFTP object and logs in using the
     * provided credentials.
     *
     * @param string $host The hostname or IP address of the SFTP server.
     * @param string $username The username for authentication.
     * @param string $password The password for authentication.
     */
    public function __construct(string $host, string $username, string $password)
    {
        try {
            $obj = new SFTP($host);
            if (!$obj->login($username, $password)) {
                throw new \RuntimeException("Failed to login to SFTP server at $host with username $username");
            }
            $this->sftp = $obj;
        } catch (\RuntimeException $e) {
            // Handle the exception, log it, or re-throw it as needed
            $error = $e->getMessage();
            $this->sftp = [
                'status' => false,
                'error' => $error
            ];
            // Optionally re-throw the exception if you want the caller to handle it
            // throw $e;
        }
    }

    public function isConnected()
    {
        return is_object($this->sftp) && $this->sftp instanceof SFTP;
    }

    /**
     * Executes a command on the SFTP server.
     *
     * This method executes the specified command on the SFTP server and returns
     * the output of the command as a string.
     *
     * @param string $command The command to be executed on the server.
     * @return string The output of the command executed on the server.
     */
    public function executeCommand(string $command): string
    {
        // Execute the specified command on the SFTP server
        return $this->sftp->exec($command);
    }

    /**
     * Changes the current directory on the SFTP server.
     *
     * This method changes the current working directory on the SFTP server
     * to the specified directory and returns the new current directory path.
     *
     * @param string $directory The directory path to change to.
     * @return string The new current directory path after the change.
     */
    public function changeDirectory(string $directory): string
    {
        // Change the current directory on the SFTP server to the specified directory
        return $this->sftp->chdir($directory);
    }

    /**
     * Retrieves a list of all files and directories from the current directory on the SFTP server.
     *
     * This method retrieves a list of all files and directories present in the current directory
     * on the SFTP server and returns them as an array.
     *
     * @return array An array containing the names of all files and directories in the current directory.
     */
    public function getAllFilesWithDirectory(): array
    {
        // Retrieve a list of all files and directories in the current directory on the SFTP server
        return $this->sftp->nlist();
    }

    /**
     * Removes a directory from the SFTP server.
     *
     * This method removes a directory from the SFTP server if it exists.
     * It first retrieves a list of all directories on the server, then checks if the
     * specified directory exists. If it exists, the method deletes the directory from
     * the server and returns true. If the directory doesn't exist, it returns false.
     *
     * @param string $path The path of the directory to remove from the server.
     * @return bool True if the directory was successfully removed, otherwise false.
     */
    public function removeDirectory($path): bool
    {
        // Get all directories currently present on the server
        $dirs = $this->getAllFilesWithDirectory();

        // Check if the specified directory exists
        if (in_array($path, $dirs)) {
            // If the directory exists, delete it from the server
            $this->sftp->delete($path, true);

            // Uncomment this line to remove directory without contents
            // $this->sftp->rmdir($path); 
            return true; // Return true to indicate successful removal of directory
        } else {
            // If the directory doesn't exist, return false
            return false;
        }
    }

    /**
     * Adds a directory to the SFTP server.
     *
     * This method adds a directory to the SFTP server if it doesn't already exist.
     * It first retrieves a list of all directories on the server, then checks if the
     * specified directory already exists. If it doesn't exist, the method creates the
     * directory on the server and returns true. If the directory already exists, it returns false.
     *
     * @param string $dir The directory path to add on the server.
     * @return bool True if the directory was successfully added, otherwise false.
     */
    public function addDirectory($dir, $ps): bool
    {
        // Get all directories currently present on the server
        $dirs = $this->getAllFilesWithDirectory(); 

        // Check if the specified directory already exists
        if (!in_array($dir, $dirs)) {
            // If the directory doesn't exist, create it on the server
            $this->sftp->mkdir($dir);

            if ($ps) {
                // Set permissions for the newly created directory
                $this->sftp->chmod(intval($ps), $dir);
            }

            // Return true to indicate successful addition of directory
            return true;
        } else {
            // If the directory already exists, return false
            return false;
        }
    }

    /**
     * Uploads a file to the remote SFTP server.
     *
     * This method uploads a file from the local filesystem to the specified remote
     * directory on the SFTP server. It first changes the current directory to the
     * remote directory, then uploads the file. If the upload is successful, it returns
     * true; otherwise, it returns false.
     *
     * @param string $localFilePath The local file path of the file to upload.
     * @param string $remotePath The remote directory path where the file will be uploaded.
     * @return bool True if the file was successfully uploaded, otherwise false.
     */
    public function uploadFile($localFilePath, $remotePath)
    {
        // Change the current directory on the remote server to the specified directory
        $this->sftp->chdir($remotePath);

        // Upload the local file to the remote directory
        if (!$this->sftp->put($remotePath . basename($localFilePath), $localFilePath, SFTP::SOURCE_LOCAL_FILE)) {
            // If the upload fails, return false
            return false;
        } else {
            // If the upload is successful, return true
            return true;
        }
    }

    /**
     * Downloads a file from a remote server via SFTP.
     *
     * This function downloads a file from a remote server using SFTP (Secure File Transfer Protocol).
     *
     * @param string $localFilePath The local file path where the downloaded file will be saved.
     * @param string $remoteFilePath The remote file path of the file to be downloaded.
     *
     * @return bool Returns true if the file was downloaded successfully, false otherwise.
     */
    public function downloadFile($localFilePath, $remoteFilePath)
    {
        // Download the file from the remote server
        if ($this->sftp->get($remoteFilePath, $localFilePath)) {
            return true; // File downloaded successfully
        } else {
            return false; // Failed to download file
        }
    }
}
