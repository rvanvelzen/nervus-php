<?php
namespace Buybrain\Nervus\Codec;

use Buybrain\Nervus\Entity;
use Buybrain\Nervus\EntityId;
use PHPUnit_Framework_TestCase;

class CodecTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider codecs
     *
     * @param Codec $codec
     */
    public function testVariousTypes(Codec $codec)
    {
        $this->assertEncodeDecode($codec, null);
        $this->assertEncodeDecode($codec, true);
        $this->assertEncodeDecode($codec, false);
        $this->assertEncodeDecode($codec, 123);
        $this->assertEncodeDecode($codec, pi());
        $this->assertEncodeDecode($codec, 'Hello there');
        $this->assertEncodeDecode($codec, [1, 2, 3]);
        $this->assertEncodeDecode($codec, ['a' => 1, 'b' => 2, 'c' => [true, false]]);
        $this->assertEncodeDecodeClass($codec, new Entity(new EntityId('test', 123), 'testing'));
        $this->assertEncodeDecodeClassList(
            $codec,
            [
                new Entity(new EntityId('test', 123), 'testing'),
                new Entity(new EntityId('test', 456), 'testing'),
            ]
        );
    }

    /**
     * @dataProvider codecs
     *
     * @param Codec $codec
     */
    public function testMultipleDocuments(Codec $codec)
    {
        $stream = fopen('php://temp', 'r+');

        $enc = $codec->newEncoder($stream);
        $dec = $codec->newDecoder($stream);

        $values = [
            new EntityId('a', 1),
            new EntityId('b', 2),
            new EntityId('c', 3),
        ];

        array_walk($values, [$enc, 'encode']);
        rewind($stream);

        $decoded = [];
        for ($i = 0; $i < count($values); $i ++) {
            $decoded[] = $dec->decode(EntityId::class);
        }

        $this->assertEquals($values, $decoded);
    }

    private function assertEncodeDecode(Codec $codec, $value)
    {
        $dec = $this->encode($codec, $value);
        $this->assertEquals($value, $dec->decode());
    }

    private function assertEncodeDecodeClass(Codec $codec, $value)
    {
        $dec = $this->encode($codec, $value);
        $this->assertEquals($value, $dec->decode(get_class($value)));
    }

    private function assertEncodeDecodeClassList(Codec $codec, array $values)
    {
        $dec = $this->encode($codec, $values);
        $this->assertEquals($values, $dec->decodeList(get_class($values[0])));
    }

    /**
     * @param Codec $codec
     * @param $value
     * @return Decoder
     */
    private function encode(Codec $codec, $value)
    {
        $stream = fopen('php://temp', 'r+');

        $enc = $codec->newEncoder($stream);
        $dec = $codec->newDecoder($stream);

        $enc->encode($value);
        rewind($stream);
        return $dec;
    }

    public function codecs()
    {
        return [
            [new JsonCodec()],
            [new PureMessagePackCodec()],
            [new NativeMessagePackCodec()],
        ];
    }
}