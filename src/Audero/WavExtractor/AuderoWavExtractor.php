<?php
namespace Audero\WavExtractor;

use Audero\WavExtractor\Wav\Wav;
use Audero\Utility\Converter;

/**
 * "Audero Wav Extractor" is a PHP library that allows to extract a chunk from a
 * wav file. The extracted chunk of the wav file can be saved on the hard disk,
 * can be forced to be prompted as download by the user's browser, returned
 * as a string for a later processing or a combination of the first and second
 * possibilities. It is very easy to use "Audero Wav Extractor" to extract a piece
 * of audio from a wav file. All you have to do is give the name of the file,
 * the start and the end time to extract (optionally you can provide a name
 * for the extracted chunk).
 *
 * The follow is an example of how to extract a chunk from a wav file and force the
 * download using this class:
 * <pre>
 *    require_once('Audero/Audero.php');
 *
 *    $inputFile = 'audio.wav'; // path to input wav file
 *    $outputFile = 'chunk.wav'; // path to the output chunk
 *    $start = 0 * 1000; // Start time of the chunk
 *    $end = 20 * 1000; // End time of the chunk
 *
 *    $extractor = new Audero($inputFile);
 *    $extractor->extractChunk($start, $end);
 * </pre>
 *
 * The follow is an example of how to extract a chunk from a wav file and save it
 * into the local disk:
 * <pre>
 *    require_once('Audero.php');
 *
 *    $inputFile = 'audio.wav'; // path to input wav file
 *    $outputFile = 'chunk.wav'; // path to the output chunk
 *    $start = 12 * 1000; // Start time of the chunk
 *    $end = 20 * 1000; // End time of the chunk
 *
 *    $extractor = new Audero($inputFile);
 *    $extractor->extractChunk($start, $end, 2, $outputFile);
 * </pre>
 *
 * LICENSE: "Audero Wav Extractor" (from now on "The software") is released under
 * the CC BY-NC 3.0 ("Creative Commons Attribution NonCommercial 3.0") license.
 * More details can be found here: http://creativecommons.org/licenses/by-nc/3.0/
 *
 * WARRANTY: The software is provided "as is", without warranty of any kind,
 * express or implied, including but not limited to the warranties of merchantability,
 * fitness for a particular purpose and noninfringement. In no event shall the
 * authors or copyright holders be liable for any claim, damages or other
 * liability, whether in an action of contract, tort or otherwise, arising from,
 * out of or in connection with the software or the use or other dealings in
 * the software.
 *
 * @package Audero\Audero
 * @author  Aurelio De Rosa <aurelioderosa@gmail.com>
 * @license http://creativecommons.org/licenses/by-nc/3.0/ CC BY-NC 3.0
 * @link    https://bitbucket.org/AurelioDeRosa/auderowavextractor
 */
class AuderoWavExtractor
{
    /**
     * The current wav file
     *
     * @var Wav
     */
    private $wav;

    /**
     * The constructor of the object. It loads all the required class and create
     * the Wav object that represents the chosen one.
     *
     * @param string $filePath The filename, including the path, of the managed file wav
     */
    public function __construct($filePath)
    {
        $this->wav = new Wav($filePath);
    }

    /**
     * Get the instance of the current wav file
     *
     * @return Wav The current wav file
     */
    public function getWav()
    {
        return $this->wav;
    }

    /**
     * Get the filename (include the path) of the current wav file.
     * If false is passed, the extension of the file is stripped.
     *
     * @param boolean $includeExtension Include or not the extension (.wav)
     *                                  of the file. Default is true.
     *
     * @return string The filename of the managed wav file
     */
    private function getFilename($includeExtension = true)
    {
        $filename = $this->wav->getFilePath();
        $start = strrpos($filename, '/');
        if ($start !== false) {
            $filename = substr($filename, $start + 1);
        }

        if ($includeExtension === false) {
            $start = strrpos($filename, '.');
            if ($start !== false) {
                $filename = substr($filename, 0, $start);
            }
        }

        return $filename;
    }

    /**
     * Extract a portion of the current, from the $start to $end. The chunk will be
     * saved, with name $filename, in the local server, the browser or the user and
     * so on based on the value of $Destination.
     *
     * The possible value of $Destination are:
     * <ul>
     *    <li>
     *       <b>1</b>: send the file to the browser and force the download
     *       with the name given by $filename.
     *    </li>
     *    <li>
     *       <b>2</b>: save into the local disk the file with the name
     *       given by $filename.
     *    </li>
     *    <li>
     *       <b>3</b>: return the wav as a string. In this case $filename is ignored.
     *    </li>
     *    <li>
     *       <b>4</b>: both 2 and 3
     *    </li>
     * </ul>
     *
     * @param int    $start       The start time, in milliseconds, of the portion to extract
     * @param int    $end         The end time, in milliseconds, of the portion to extract
     * @param int    $destination Where to save the extracted chunk
     * @param string &$filename   The name to assign to the chunk. If no name is provided
     *                            the method generate one using this rule:
     *                            "InputFilename-Start-End.wav"
     *
     * @return string The chunk to extract, including the headers
     *
     * @throws \InvalidArgumentException If the give boundary is invalid (for example
     * if the end is lower than start) or if the destination value isn't valid
     * (for example greater than 4).
     * @throws \RuntimeException If there's not enough memory to save the given range
     */
    public function extractChunk($start, $end, $destination = 1, &$filename = '')
    {
        $start = (int)$start;
        $end = (int)$end;
        if ($start < 0 || $start > $this->wav->getDuration()
            || $end < 0 || $end > $this->wav->getDuration()
            || $start >= $end
        ) {
            throw new \InvalidArgumentException('Invalid chunk boundaries');
        }

        $destination = (int)$destination;
        if ($destination < 1 || $destination > 4) {
            throw new \InvalidArgumentException('Invalid Destination value');
        }

        if (empty($filename)) {
            $filename = $this->getFilename(false) . "-$start-$end.wav";
        }

        if ($this->isEnoughMemory($start, $end) === false) {
            throw new \RuntimeException('Not enough memory to save the given range');
        }

        $size = ($end - $start) * $this->wav->getDataRate() / 1000;
        $difference = $size % $this->wav->getChannelsNumber();
        if ($difference != 0) {
            $size += $difference;
        }
        $size = (int)$size;

        $this->wav->setDataChunkSize($size);

        $chunk = $this->wav->headersToString();
        $chunk .= $this->wav->getWavChunk($start, $end);

        switch ($destination) {
            case 1:
                $this->downloadChuck($chunk, $filename);
                unset($chunk);
                $chunk = null;
                break;
            case 2:
                $this->saveChunk($chunk, $filename);
                unset($chunk);
                $chunk = null;
                break;
            case 3:
                break;
            case 4:
                $this->saveChunk($chunk, $filename);
                break;
        }

        return $chunk;
    }

    /**
     * This method sets the headers to force the download and send the data
     * of the chunk.
     *
     * @param string $Chunk    The string containing the bytes of the chunk
     * @param string $Filename The filename that will be shown by the browser
     *
     * @return void
     */
    private function downloadChuck($Chunk, $Filename)
    {
        // Clear any previous data sent
        $Output = ob_get_contents();
        if (!empty($Output) || headers_sent() === true) {
            ob_clean();
        }

        header('Content-Description: File Transfer');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Fri, 06 Nov 1987 12:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Type: audio/x-wav', false);
        header('Content-Disposition: attachment; filename="' . basename($Filename) . '";');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($Chunk));
        echo $Chunk;
    }

    /**
     * Save the extracted chunk on the hard disk
     *
     * @param string $Chunk    The string containing the bytes of the chunk
     * @param string $Filename The filename to use to save the file on the hard disk
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the chunk is empty
     * @throws \Exception If the library is unable to create the file on the hard disk
     */
    private function saveChunk($Chunk, $Filename)
    {
        if (empty($Chunk)) {
            throw new \InvalidArgumentException('Invalid chunk');
        }

        $File = @fopen($Filename, 'wb');
        if ($File === false) {
            throw new \Exception('Unable to create the file on the disk');
        }
        fwrite($File, $Chunk);
        fclose($File);
    }

    /**
     * Try to test if the available memory is enough to extract the chunk
     *
     * @param int $Start The start time, in milliseconds, of the chunk that
     *                   has to be extracted
     * @param int $End   The end time, in milliseconds, of the chunk that
     *                   has to be extracted
     *
     * @return bool <code>true</code> if the memory is enough. <code>False</code> otherwise.
     */
    public function isEnoughMemory($Start, $End)
    {
        $FromByte = Converter::millisecondsToByte($Start, $this->wav->getDataRate());
        $ToByte = Converter::millisecondsToByte($End, $this->wav->getDataRate());

        $MemoryLimit = (int)ini_get('memory_limit');
        $MemoryLimit = Converter::megabyteToByte($MemoryLimit);
        $MemoryUsage = memory_get_usage();
        $ExpectedMemoryAllocation = $this->wav->getHeadersSize() + $ToByte - $FromByte;

        return ($ExpectedMemoryAllocation + $MemoryUsage <= $MemoryLimit);
    }
}