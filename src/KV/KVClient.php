<?php declare(strict_types=1);

namespace DCarbone\PHPConsulAPI\KV;

/*
   Copyright 2016-2021 Daniel Carbone (daniel.p.carbone@gmail.com)

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
 */

use DCarbone\Go\HTTP;
use DCarbone\PHPConsulAPI\AbstractClient;
use DCarbone\PHPConsulAPI\Error;
use DCarbone\PHPConsulAPI\QueryOptions;
use DCarbone\PHPConsulAPI\Request;
use DCarbone\PHPConsulAPI\ValuedQueryStringsResponse;
use DCarbone\PHPConsulAPI\ValuedWriteBoolResponse;
use DCarbone\PHPConsulAPI\WriteOptions;
use DCarbone\PHPConsulAPI\WriteResponse;

/**
 * Class KVClient
 */
class KVClient extends AbstractClient
{
    /**
     * @param string $key
     * @param \DCarbone\PHPConsulAPI\QueryOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\KV\KVPairResponse
     */
    public function Get(string $key, ?QueryOptions $opts = null): KVPairResponse
    {
        $res = $this->_doGet(\sprintf('v1/kv/%s', $key), $opts);
        if (null !== $res->Err) {
            return new KVPairResponse(null, null, $res->Err);
        }

        $code = $res->Response->getStatusCode();

        if (200 === $code) {
            [$data, $err] = $this->decodeBody($res->Response->getBody());

            if (null !== $err) {
                return new KVPairResponse(null, null, $err);
            }

            $qm = $this->buildQueryMeta($duration, $res, $r->getUri());
            return new KVPairResponse($data[0], $qm, null);
        }

        $qm = $this->buildQueryMeta($duration, $res, $r->getUri());

        if (404 === $code) {
            return new KVPairResponse(null, $qm, null);
        }

        return new KVPairResponse(
            null,
            $qm,
            new Error(\sprintf('%s: %s', $res->getStatusCode(), $res->getReasonPhrase()))
        );
    }

    /**
     * @param \DCarbone\PHPConsulAPI\KV\KVPair $p
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\WriteResponse
     */
    public function Put(KVPair $p, ?WriteOptions $opts = null): WriteResponse
    {
        $r = new Request(HTTP\MethodPut, \sprintf('v1/kv/%s', $p->Key), $this->config, $p->Value);
        $r->applyOptions($opts);
        if (0 !== $p->Flags) {
            $r->params->set('flags', (string)$p->Flags);
        }

        [$duration, $_, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new WriteResponse(null, $err);
        }

        return new WriteResponse($this->buildWriteMeta($duration), null);
    }

    /**
     * @param string $key
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\WriteResponse
     */
    public function Delete(string $key, ?WriteOptions $opts = null): WriteResponse
    {
        $r = new Request(HTTP\MethodDelete, \sprintf('v1/kv/%s', $key), $this->config, null);
        $r->applyOptions($opts);

        [$duration, $_, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new WriteResponse(null, $err);
        }

        return new WriteResponse($this->buildWriteMeta($duration), null);
    }

    /**
     * @param string $prefix
     * @param \DCarbone\PHPConsulAPI\QueryOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\KV\KVPairsResponse
     */
    public function List(string $prefix = '', ?QueryOptions $opts = null): KVPairsResponse
    {
        $r = new Request('GET', \sprintf('v1/kv/%s', $prefix), $this->config, null);
        $r->applyOptions($opts);
        $r->params->set('recurse', '');

        /** @var \Psr\Http\Message\ResponseInterface $response */
        [$duration, $response, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new KVPairsResponse(null, null, $err);
        }

        [$data, $err] = $this->decodeBody($response->getBody());
        if (null !== $err) {
            return new KVPairsResponse(null, null, $err);
        }

        $qm = $this->buildQueryMeta($duration, $response, $r->getUri());

        return new KVPairsResponse($data, $qm, null);
    }

    /**
     * @param string $prefix
     * @param \DCarbone\PHPConsulAPI\QueryOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\ValuedQueryStringsResponse
     */
    public function Keys(string $prefix = '', ?QueryOptions $opts = null): ValuedQueryStringsResponse
    {
        $r = new Request('GET', \sprintf('v1/kv/%s', $prefix), $this->config, null);
        $r->applyOptions($opts);
        $r->params->set('keys', '');

        /** @var \Psr\Http\Message\ResponseInterface $response */
        [$duration, $response, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new ValuedQueryStringsResponse(null, null, $err);
        }

        [$data, $err] = $this->decodeBody($response->getBody());
        if (null !== $err) {
            return new ValuedQueryStringsResponse(null, null, $err);
        }

        return new ValuedQueryStringsResponse($data, $this->buildQueryMeta($duration, $response, $r->getUri()), $err);
    }

    /**
     * @param \DCarbone\PHPConsulAPI\KV\KVPair $p
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\ValuedWriteBoolResponse
     */
    public function CAS(KVPair $p, ?WriteOptions $opts = null): ValuedWriteBoolResponse
    {
        $r = new Request('PUT', \sprintf('v1/kv/%s', $p->Key), $this->config, $p->Value);
        $r->applyOptions($opts);
        $r->params->set('cas', (string)$p->ModifyIndex);
        if (0 !== $p->Flags) {
            $r->params->set('flags', (string)$p->Flags);
        }

        /** @var \Psr\Http\Message\ResponseInterface $response */
        [$duration, $response, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new ValuedWriteBoolResponse(false, null, $err);
        }

        return new ValuedWriteBoolResponse(
            0 === \strpos($response->getBody()->getContents(), 'true'),
            $this->buildWriteMeta($duration),
            null
        );
    }

    /**
     * @param \DCarbone\PHPConsulAPI\KV\KVPair $p
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\WriteResponse
     */
    public function Acquire(KVPair $p, ?WriteOptions $opts = null): WriteResponse
    {
        $r = new Request('PUT', \sprintf('v1/kv/%s', $p->Key), $this->config, $p->Value);
        $r->applyOptions($opts);
        $r->params->set('acquire', $p->Session);
        if (0 !== $p->Flags) {
            $r->params->set('flags', (string)$p->Flags);
        }

        [$duration, $_, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new WriteResponse(null, $err);
        }

        return new WriteResponse($this->buildWriteMeta($duration), null);
    }

    /**
     * @param \DCarbone\PHPConsulAPI\KV\KVPair $p
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\ValuedWriteBoolResponse
     */
    public function DeleteCAS(KVPair $p, ?WriteOptions $opts = null): ValuedWriteBoolResponse
    {
        $r = new Request('DELETE', \sprintf('v1/kv/%s', \ltrim($p->Key, '/')), $this->config, null);
        $r->applyOptions($opts);
        $r->params['cas'] = (string)$p->ModifyIndex;

        /** @var \Psr\Http\Message\ResponseInterface $response */
        [$duration, $response, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new ValuedWriteBoolResponse(false, null, $err);
        }

        return new ValuedWriteBoolResponse(
            0 === \strpos($response->getBody()->getContents(), 'true'),
            $this->buildWriteMeta($duration),
            null
        );
    }

    /**
     * @param \DCarbone\PHPConsulAPI\KV\KVPair $p
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\WriteResponse
     */
    public function Release(KVPair $p, ?WriteOptions $opts = null): WriteResponse
    {
        $r = new Request('PUT', \sprintf('v1/kv/%s', $p->Key), $this->config, $p->Value);
        $r->applyOptions($opts);
        $r->params->set('release', $p->Session);
        if (0 !== $p->Flags) {
            $r->params->set('flags', (string)$p->Flags);
        }

        [$duration, $_, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new WriteResponse(null, $err);
        }

        return new WriteResponse($this->buildWriteMeta($duration), null);
    }

    /**
     * @param string $prefix
     * @param \DCarbone\PHPConsulAPI\WriteOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\WriteResponse
     */
    public function DeleteTree(string $prefix, ?WriteOptions $opts = null): WriteResponse
    {
        $r = new Request('DELETE', \sprintf('v1/kv/%s', $prefix), $this->config, null);
        $r->params['recurse'] = '';
        $r->applyOptions($opts);

        [$duration, $_, $err] = $this->_requireOK($this->_do($r));
        if (null !== $err) {
            return new WriteResponse(null, $err);
        }

        return new WriteResponse($this->buildWriteMeta($duration), null);
    }

    /**
     * @param \DCarbone\PHPConsulAPI\KV\KVTxnOps $txn
     * @param \DCarbone\PHPConsulAPI\QueryOptions|null $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return \DCarbone\PHPConsulAPI\KV\KVTxnAPIResponse
     */
    public function Txn(KVTxnOps $txn, ?QueryOptions $opts = null): KVTxnAPIResponse
    {
        $ops = new KVTxnOps();
        foreach ($txn as $op) {
            $ops->append(clone $op);
        }

        $r = new Request('PUT', 'v1/txn', $this->config, $ops);
        $r->applyOptions($opts);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        [$duration, $response, $err] = $this->_do($r);
        if (null !== $err) {
            return new KVTxnAPIResponse(false, null, null, $err);
        }

        $qm = $this->buildQueryMeta($duration, $response, $r->getUri());

        $code = $response->getStatusCode();
        if (200 === $code || 409 === $code) {
            [$data, $err] = $this->decodeBody($response->getBody());
            if (null !== $err) {
                return new KVTxnAPIResponse(false, null, null, $err);
            }

            // TODO: Maybe go straight to actual response?  What is the benefit of this...
            $internal = new TxnResponse($data);

            $resp = new KVTxnResponse(['Errors' => $internal->Errors, 'Results' => $internal->Results]);
            return new KVTxnAPIResponse(200 === $code, $resp, $qm, null);
        }

        if ('' === ($body = $response->getBody()->getContents())) {
            return new KVTxnAPIResponse(false, null, null, new Error('Unable to read response'));
        }

        return new KVTxnAPIResponse(false, null, null, new Error('Failed request: ' . $body));
    }

    /**
     * @param string|null $prefix
     * @param \DCarbone\PHPConsulAPI\QueryOptions $opts
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @return array(
     * @var \DCarbone\PHPConsulAPI\KV\KVPair[]|\DCarbone\PHPConsulAPI\KV\KVTree[]|null array of trees, values, or null on error
     * @var \DCarbone\PHPConsulAPI\Error|null error, if any
     * )
     */
    public function Tree(string $prefix = '', ?QueryOptions $opts = null): array
    {
        [$valueList, $_, $err] = $this->List($prefix, $opts);

        if (null !== $err) {
            return [null, $err];
        }

        $treeHierarchy = [];
        foreach ($valueList as $path => $kvp) {
            $slashPos = \strpos($path, '/');
            if (false === $slashPos) {
                $treeHierarchy[$path] = $kvp;
                continue;
            }

            $root = \substr($path, 0, $slashPos + 1);

            if (!isset($treeHierarchy[$root])) {
                $treeHierarchy[$root] = new KVTree($root);
            }

            if ('/' === \substr($path, -1)) {
                $_path = '';
                foreach (\explode('/', $prefix) as $part) {
                    if ('' === $part) {
                        continue;
                    }

                    $_path .= "{$part}/";

                    if ($root === $_path) {
                        continue;
                    }

                    if (!isset($treeHierarchy[$root][$_path])) {
                        $treeHierarchy[$root][$_path] = new KVTree($_path);
                    }
                }
            } else {
                $kvPrefix = \substr($path, 0, \strrpos($path, '/') + 1);
                $_path = '';
                foreach (\explode('/', $kvPrefix) as $part) {
                    if ('' === $part) {
                        continue;
                    }

                    $_path .= "{$part}/";

                    if ($root === $_path) {
                        continue;
                    }

                    if (!isset($treeHierarchy[$root][$_path])) {
                        $treeHierarchy[$root][$_path] = new KVTree($_path);
                    }
                }

                $treeHierarchy[$root][$path] = $kvp;
            }
        }
        return [$treeHierarchy, null];
    }
}
