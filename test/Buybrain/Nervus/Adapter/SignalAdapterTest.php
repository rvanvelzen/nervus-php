<?php
namespace Buybrain\Nervus\Adapter;

use Buybrain\Nervus\Adapter\Config\AdapterConfig;
use Buybrain\Nervus\Adapter\Config\SignalAdapterConfig;
use Buybrain\Nervus\EntityId;
use Buybrain\Nervus\TestIO;

class SignalAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testSignalAdapter()
    {
        $request = new SignalRequest();
        $signal = new Signal([new EntityId('test', '123')]);
        $response = new SignalAckRequest(true);

        $io = (new TestIO())->write($request)->write($response);

        $SUT = (new MockSignalAdapter($signal))
            ->in($io->input())
            ->out($io->output())
            ->codec($io->codec())
            ->interval(10);

        $SUT->step();

        $expected =
            json_encode(new AdapterConfig($io->codec()->getName(), 'signal', ['test'], new SignalAdapterConfig(10))) .
            $io->encode(SignalResponse::success($signal), SignalAckResponse::success());

        $this->assertEquals($expected, $io->writtenData());
        $this->assertTrue($SUT->getResponse());
    }
}
