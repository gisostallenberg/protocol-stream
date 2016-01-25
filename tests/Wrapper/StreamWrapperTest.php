<?php

namespace Nijens\ProtocolStream\Test\Wrapper;

use Nijens\ProtocolStream\Stream\Stream;
use Nijens\ProtocolStream\StreamManager;
use PHPUnit_Framework_TestCase;

/**
 * StreamWrapperTest.
 *
 * @author Niels Nijens <nijens.niels@gmail.com>
 */
class StreamWrapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * Registers the streams.
     */
    public function setUp()
    {
        $stream = new Stream('test', array(realpath(__DIR__.'/../Resources/')), true);
        $streamReadOnly = new Stream('test-read', array(realpath(__DIR__.'/../Resources/')), false);

        StreamManager::create()
                ->registerStream($stream)
                ->registerStream($streamReadOnly);
    }

    /**
     * Unregisters the streams.
     */
    public function tearDown()
    {
        StreamManager::create()
                ->unregisterStream('test')
                ->unregisterStream('test-read');
    }

    /**
     * Tests if the stream wrapper returns the expected result when reading a directory.
     */
    public function testDirectoryRead()
    {
        $this->assertSame(array('.', '..', 'directory', 'file.ext'), scandir('test://./'));
    }

    /**
     * Tests if the stream wrapper returns the expected result when rewinding a directory handle.
     */
    public function testDirectoryRewind()
    {
        $directoryResource = opendir('test://./');
        $entry = readdir($directoryResource);
        rewinddir($directoryResource);

        $this->assertSame($entry, readdir($directoryResource));
    }

    /**
     * Tests if the stream wrapper creates a directory.
     */
    public function testDirectoryCreate()
    {
        $this->assertTrue(mkdir('test://directory/in-a-directory'));
    }

    /**
     * Tests if the creation of a directory fails on a read-only stream.
     */
    public function testDirectoryCreateFailsOnReadOnlyStream()
    {
        $this->assertFalse(mkdir('test-read://directory/in-a-directory'));
    }

    /**
     * Tests if the stream wrapper removes a directory.
     */
    public function testDirectoryRemove()
    {
        $this->assertTrue(rmdir('test://directory/in-a-directory'));
    }

    /**
     * Tests if the stream wrapper touches a file.
     */
    public function testTouch()
    {
        $this->assertTrue(touch('test://directory/with-a-file.ext'));
        $this->assertFileExists(__DIR__.'/../Resources/directory/with-a-file.ext');
    }

    /**
     * Tests if touching a file fails on a read-only stream.
     */
    public function testTouchFailsOnReadOnlyStream()
    {
        $this->assertFalse(touch('test-read://directory/with-a-file.ext'));
    }

    /**
     * Tests if the stream wrapper unlinks a file.
     */
    public function testUnlink()
    {
        $this->assertTrue(unlink('test://directory/with-a-file.ext'));
        $this->assertFileNotExists(__DIR__.'/../Resources/directory/with-a-file.ext');
    }

    /**
     * Tests if unlinking a file fails on a read-only stream.
     */
    public function testUnlinkFailsOnReadOnlyStream()
    {
        $this->assertFalse(touch('test-read://file.ext'));
    }

    /**
     * Tests if the stream wrapper reads a file.
     */
    public function testReadFile()
    {
        $this->assertSame("contents\n", file_get_contents('test://file.ext'));
    }

    /**
     * Tests if the stream wrapper writes to a file.
     *
     * @depends testUnlink
     */
    public function testWriteFile()
    {
        $this->assertSame(9, file_put_contents('test://written-file.ext', "contents\n"));
        $this->assertFileExists(__DIR__.'/../Resources/written-file.ext');

        unlink('test://written-file.ext');
    }

    /**
     * Tests if writing to a file fails on a read-only stream.
     */
    public function testWriteFileFailsOnReadOnlyStream()
    {
        $this->assertFalse(@file_put_contents('test-read://written-file.ext', "contents\n"));
    }

    /**
     * Tests if the stream wrapper renames a file.
     */
    public function testRename()
    {
        $this->assertTrue(rename('test://file.ext', 'test://file.ext'));
    }

    /**
     * Tests if renaming a file fails on a read-only stream.
     */
    public function testRenameFailsOnReadOnlyStream()
    {
        $this->assertFalse(rename('test-read://file.ext', 'test-read://file.ext'));
    }

    /**
     * Tests if the stream wrapper does not allow access to files outside of the allowed paths.
     *
     * @dataProvider provideTestStreamWrapperPathEscaping
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testStreamWrapperPathEscaping($path)
    {
        file_get_contents($path);
    }

    /**
     * Returns an array with testcases for @see testStreamWrapperPathEscaping.
     *
     * @return array
     */
    public function provideTestStreamWrapperPathEscaping()
    {
        return array(
            array('test://./../'),
            array('test://./directory/../../../file.ext'),
            array('test://.//directory//../../../file.ext'),
            array('test://./../StreamManagerTest.php'),
            array('test://./../../../../../../../../../StreamManagerTest.php'),
        );
    }
}
