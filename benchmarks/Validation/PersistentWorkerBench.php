<?php

namespace Illuminate\Benchmarks\Validation;

use PhpBench\Attributes as Bench;

#[Bench\Iterations(10)]
#[Bench\AfterMethods(['verifyState'])]
class PersistentWorkerBench extends WorkerBenchmark
{
    #[Bench\BeforeMethods(['prepareParser'])]
    #[Bench\ParamProviders(['parserCases'])]
    #[Bench\Revs(2000)]
    #[Bench\Warmup(50)]
    public function benchParseRequest(): void
    {
        $this->parseRequest();
    }

    #[Bench\BeforeMethods(['prepareValidator'])]
    #[Bench\ParamProviders(['validatorCases'])]
    #[Bench\Revs(200)]
    #[Bench\Warmup(20)]
    public function benchValidateRequest(): void
    {
        $this->validateRequest();
    }
}
