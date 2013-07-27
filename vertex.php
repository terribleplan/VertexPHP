<?php
/**
 * A class that handles using VertexDB as a database source. (http://www.dekorte.com/projects/opensource/vertexdb/)
 *
 * Official VertexDB documentation: http://www.dekorte.com/projects/opensource/vertexdb/docs/manual.html
 *
 * Author: terribleplan
 * Version 0.1-alpha
 * License: BSD-3Clause
 */
 /*
Copyright (c) 2013, terribleplan
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
 * Neither the name of Verineu LLC nor the names of its contributors may be
   used to endorse or promote products derived from this software without
   specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
 */
class VDB_Action {
	private $r;
	private $d;
	function __construct($r, $d) {

	}
	function _do($vdb) {
		$vdb->low_ignore($this->r, $this-d);
	}
}
class VertexDB {
	private $location;
	private $transact;
	private $tranStore = array();
	private $ch;
	/**
	 * $connection is a string in the standard http://<host>:<port>/ format
	 */
	function __construct($connection) {
		$this->location = $connection;
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	}

	public function good() {
		return ($this->size('/') >= 0);
	}

	/**
	 * Sets the connection to operate in transaction mode
	 * NOTE: this only applies to write/modify actions
	 * NOTE: There is no error checking, or roll-back. Use at your own risk.
	 */
	public function startTransaction() {
		$this->transact = true;
	}
	/**
	 * Commits all write actions to the server.
	 * NOTE: This does NOT use the server's transaction feature, because it does not allow 'write' actions.
	 * NOTE: There is no error checking, or roll-back. Use at your own risk.
	 */
	public function commitTransaction() {
		foreach ($this->tranStore as $a) {
			$a->_do($this);
		}
		$this->transact = false;
		$this->tranStore = array();
	}

	//NODE
	/**
	 * 
	 */
	public function mkdir($path) {
		$a = $path . '?action=mkdir';
		if ($this->transact) {
			$this->tranStore[count($tranStore)] = new VDB_Action($a, false);
		} else {
			return ($this->low($a, false) == 'null');
		}
		return true;
	}
	public function rm($path) {
		$a = $path . '?action=rm';
		print("\n".$a."\n");
		if ($this->transact) {
			$this->tranStore[count($tranStore)] = new VDB_Action($a, false);
		} else {
			return ($this->low($a, false) == 'null');
		}
		return true;
	}
	public function size($path) {
		return $this->low($path . '?action=size', false);
	}
	public function link($key, $source, $destination) {
		$a = $source . '/?action=link&key=' . $key . '&toPath=' . $source;
		if ($this->transact) {
			$this->tranStore[count($tranStore)] = new VDB_Action($a, false);
		} else {
			return $this->low($a, false);
		}
		return true;
	}

	//VALUE
	public function read($path) {
		return $this->low($path . '?action=read');
	}
	public function write($path, $key, $value, $append=false) {
		if ($append) {
			$mode = 'append';
		} else {
			$mode = 'set';
		}
		$a = $path . '?action=write&key=' . $key . '&mode=' . $mode;
		if ($this->transact) {
			$this->tranStore[count($tranStore)] = new VDB_Action($a, $value);
		} else {
			return $this->low($a, $value);
		}
	}

	//SELECT
	public function select_pairs($path, $before='', $after='', $count=-1, $where=null) {
		return select_parse_return($this->low($path . '?action=select&op=pairs' . $this->select_parse_options($before, $after, $count, $where)));
	}
	public function select_keys($path, $before='', $after='', $count=-1, $where=null) {
		return select_parse_return($this->low($path . '?action=select&op=keys' . $this->select_parse_options($before, $after, $count, $where)));
	}
	public function select_values($path, $before='', $after='', $count=-1, $where=null) {
		return select_parse_return($this->low($path . '?action=select&op=values' . $this->select_parse_options($before, $after, $count, $where)));
	}
	public function select_object($path, $before='', $after='', $count=-1, $where=null) {
		return select_parse_return($this->low($path . '?action=select&op=object' . $this->select_parse_options($before, $after, $count, $where)));
	}
	public function select_counts($path, $before='', $after='', $count=-1, $where=null) {
		return select_parse_return($this->low($path . '?action=select&op=counts' . $this->select_parse_options($before, $after, $count, $where)));
	}
	public function select_rm($path, $before='', $after='', $count=-1, $where=null) {
		$a = $path . '?action=select&op=rm' . $this->select_parse_options($before, $after, $count, $where);
		print("\n".$a."\n");
		if ($this->transact) {
			$this->tranStore[count($tranStore)] = new VDB_Action($a);
		} else {
			return $this->low($a);
		}
	}
	private function select_parse_options($before, $after, $count, $where) {
		if ($before !== '') {
			$before = '&before=' . urlencode($before);
		}
		if ($after !== '') {
			$after = '&after=' . urlencode($after);
		}
		if ($count > 0) {
			$count = '&count=' . urlencode($count);
		} else {
			$count = '';
		}
		if (is_array($where) && (count($where) == 2) && ((bool)count(array_filter(array_keys($array), 'is_string')))) {
			$where = '&whereKey=' . urlencode($where[0]) . '&whereValue=' . urlencode($where[1]);
		} else {
			$where = '';
		}
		return $before . $after . $count . $where;
	}
	private function select_parse_return($value='[]') {
		$retval = json_decode('{"a":' . $value . '}');
		return $retval->a;
	}

	//QUEUE
	public function queue_pop_to($source, $destination, $ttl=-1, $where=null) {
		if ($ttl > 0) {
			$ttl = '&ttl=' . urlencode($ttl);
		} else {
			$ttl = '';
		}
		if (is_array($where) && (count($where) == 2) && ((bool)count(array_filter(array_keys($array), 'is_string')))) {
			$where = '&whereKey=' . urlencode($where[0]) . '&whereValue=' . urlencode($where[1]);
		} else {
			$where = '';
		}
		$a = $source . '/?action=queuePopTo&toPath=' . $destination . $ttl . $where;
		if ($this->transact) {
			$this->tranStore[count($tranStore)] = new VDB_Action($a);
		} else {
			return $this->low($a);
		}
	}
	public function queue_expire_to($source, $destiantion) {
		$a = $source . '/?action=queueExpireTo&toPath=' . $destination;
		if ($this->transact) {
			$this->tranStore[count($tranStore)] = new VDB_Action($a);
		} else {
			return $this->low($a);
		}
	}

	//LOWEST EXPOSED LEVEL
	//USE AT YOUR OWN RISK
	protected function low($request, $data=false) {
		if ($data === false) {
			$t = $this->http_get($this->location . $request);
		} else {
			$t = $this->http_post($this->location . $request, $data);
		}
		return $t;
	}
	protected function low_ignore($request, $data=false) {
		try {
			if ($data === false) {
				$this->http_get($this->location . $request);
			} else {
				$this->http_post($this->location . $request, $data);
			}
		} catch (Exception $e) {}
	}

	//RAW HTTP FUNCTIONS
	private function http_post($url, $data) {
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		return curl_exec($this->ch);
	}
	private function http_get($url) {
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);
		return curl_exec($this->ch);
	}
}
?>