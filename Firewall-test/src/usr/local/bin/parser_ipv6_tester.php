#!/usr/local/bin/php -f
<?php
/*
 * parser_ipv6_tester.php
 *
 * Copyright (c) 2017-2019 Anders Lind (anders.lind@gmail.com)
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

$bjerkerudFirewall_platform_file = '/etc/platform';

$is_bjerkerudFirewall = file_exists($bjerkerudFirewall_platform_file);
if ($is_bjerkerudFirewall) {
	if ( ! preg_match('/^bjerkerudFirewall/i', file_get_contents($bjerkerudFirewall_platform_file))) {
		$is_bjerkerudFirewall = false;
	}
}

if ($is_bjerkerudFirewall) {
	require_once('tools_for_debugging_n_testing.inc');
	require_once('parser_ipv6.inc');
} else {
	/* Handle situation where we use source files on a non-running bjerkerudFirewall system.
	 * Get from this file back to 'src' so we can use 'src/etc/inc/'.
	 */
	define('__ROOT__', dirname(dirname(dirname(dirname(__FILE__)))));
	require_once(__ROOT__.'/etc/inc/tools_for_debugging_n_testing.inc');
	require_once(__ROOT__.'/etc/inc/parser_ipv6.inc');
}

/*
 * Tests the content for valid IPv6 addresses and compares the matches against
 * a checklist. Each checklist entry is also separately matched and compared
 * against itself. The order of the resulting matches must be identical to the
 * order of the list entries to provide successful passes.
 *
 * Amount of matches should always equal amount of list entries in the
 * checklist.
 * Passes are for successful matches.
 * Fails should always stay 0.
 * Fails are for iterations where:
 * * comparison between a current match of text and a current list entry are
 *   unequal in value.
 * * a list entry is unable to be matched when matching is done on the checklist
 *   alone or the comparison against itself fails.
 * It is quite ok that a text and list of invalid addresses evaluates to
 * zero fails and zero passes, because of the above!
 */
function test_parse_ipv6($content, $checklist, $only_errors) {
	$amount_of_matches = preg_match_all('/'.ipv6_regex.'/i', $content, $matches, PREG_SET_ORDER);

	$amount_of_entries = count($checklist);
	if ($amount_of_matches != $amount_of_entries) {
		echo "ERROR: Amount of matches does not equal amount of entries in the".
        	     " check list: $amount_of_matches != $amount_of_entries\n";
		return false;
	}

	$counter = 0;
	$fail_counter = 0;
	foreach ($matches as $val) {
		$next = $checklist[$counter];
		if (strcmp($val['MATCH'], $next) ) {
			echo "FAIL: '".$val['MATCH']."' != '$next' \n";
			$fail_counter += 1;
		} elseif ( ! $only_errors) {
			if (setnlen($val, 'IPV64')) {
				echo "IPv6+IPv4: ".$val['MATCH']."\n";
			} elseif (setnlen($val, 'IPV6')) {
				echo "IPv6: ".$val['MATCH']."\n";
			} else {
				echo ":-o undefined type: ".$val['MATCH']."\n";
			}
		}

		$counter += 1;
	}
	echo "Testing against text\n";
	echo "Total amount of fails: $fail_counter\n";
	echo "Total amount of passes: ".($counter - $fail_counter)."\n";

	$counter = 0;
	$fail_counter = 0;
	foreach ($checklist as $sample) {
		if (preg_match('/'.ipv6_regex.'/i', $sample, $matches)) {
			if (strcmp($matches['MATCH'], $sample) ) {
				echo "FAIL: '".$matches['MATCH']."' != '$sample' \n";
				$fail_counter += 1;
			} elseif ( ! $only_errors) {
	        		if (setnlen($matches, 'IPV64')) {
	        			echo "IPv6+IPv4: ".$matches['MATCH']."\n";
				} elseif (setnlen($matches, 'IPV6')) {
					echo "IPv6: ".$matches['MATCH']."\n";
				} else {
					echo ":-o undefined type: ".$matches['MATCH']."\n";
				}
			}
		} else {
			echo "FAIL: Unable to match '$sample' \n";
			$fail_counter += 1;
		}

		$counter += 1;
	}
	echo "Testing against list entries\n";
	echo "Total amount of fails: $fail_counter\n";
	echo "Total amount of passes: ".($counter - $fail_counter)."\n";
}

/*
 * Below lists with permission from Aeron of http://home.deds.nl/~aeron/regex/
 * aeron_list
 * aeron_text
 * aeron_invalid_text
 * Thanks! :)
 *
 * http://home.deds.nl/~aeron/regex/valid_ipv6.txt
 */
const aeron_list = [
'1111:2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444:5555:6666:7777::',
'1111:2222:3333:4444:5555:6666::',
'1111:2222:3333:4444:5555::',
'1111:2222:3333:4444::',
'1111:2222:3333::',
'1111:2222::',
'1111::',
'::',
'1111:2222:3333:4444:5555:6666::8888',
'1111:2222:3333:4444:5555::8888',
'1111:2222:3333:4444::8888',
'1111:2222:3333::8888',
'1111:2222::8888',
'1111::8888',
'::8888',
'1111:2222:3333:4444:5555::7777:8888',
'1111:2222:3333:4444::7777:8888',
'1111:2222:3333::7777:8888',
'1111:2222::7777:8888',
'1111::7777:8888',
'::7777:8888',
'1111:2222:3333:4444::6666:7777:8888',
'1111:2222:3333::6666:7777:8888',
'1111:2222::6666:7777:8888',
'1111::6666:7777:8888',
'::6666:7777:8888',
'1111:2222:3333::5555:6666:7777:8888',
'1111:2222::5555:6666:7777:8888',
'1111::5555:6666:7777:8888',
'::5555:6666:7777:8888',
'1111:2222::4444:5555:6666:7777:8888',
'1111::4444:5555:6666:7777:8888',
'::4444:5555:6666:7777:8888',
'1111::3333:4444:5555:6666:7777:8888',
'::3333:4444:5555:6666:7777:8888',
'::2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444:5555:6666:123.123.123.123',
'1111:2222:3333:4444:5555::123.123.123.123',
'1111:2222:3333:4444::123.123.123.123',
'1111:2222:3333::123.123.123.123',
'1111:2222::123.123.123.123',
'1111::123.123.123.123',
'::123.123.123.123',
'1111:2222:3333:4444::6666:123.123.123.123',
'1111:2222:3333::6666:123.123.123.123',
'1111:2222::6666:123.123.123.123',
'1111::6666:123.123.123.123',
'::6666:123.123.123.123',
'1111:2222:3333::5555:6666:123.123.123.123',
'1111:2222::5555:6666:123.123.123.123',
'1111::5555:6666:123.123.123.123',
'::5555:6666:123.123.123.123',
'1111:2222::4444:5555:6666:123.123.123.123',
'1111::4444:5555:6666:123.123.123.123',
'::4444:5555:6666:123.123.123.123',
'1111::3333:4444:5555:6666:123.123.123.123',
'::3333:4444:5555:6666:123.123.123.123',
'::2222:3333:4444:5555:6666:123.123.123.123'];

const aeron_text = <<< 'AERON'
1111:2222:3333:4444:5555:6666:7777:8888
1111:2222:3333:4444:5555:6666:7777::
1111:2222:3333:4444:5555:6666::
1111:2222:3333:4444:5555::
1111:2222:3333:4444::
1111:2222:3333::
1111:2222::
1111::
::
1111:2222:3333:4444:5555:6666::8888
1111:2222:3333:4444:5555::8888
1111:2222:3333:4444::8888
1111:2222:3333::8888
1111:2222::8888
1111::8888
::8888
1111:2222:3333:4444:5555::7777:8888
1111:2222:3333:4444::7777:8888
1111:2222:3333::7777:8888
1111:2222::7777:8888
1111::7777:8888
::7777:8888
1111:2222:3333:4444::6666:7777:8888
1111:2222:3333::6666:7777:8888
1111:2222::6666:7777:8888
1111::6666:7777:8888
::6666:7777:8888
1111:2222:3333::5555:6666:7777:8888
1111:2222::5555:6666:7777:8888
1111::5555:6666:7777:8888
::5555:6666:7777:8888
1111:2222::4444:5555:6666:7777:8888
1111::4444:5555:6666:7777:8888
::4444:5555:6666:7777:8888
1111::3333:4444:5555:6666:7777:8888
::3333:4444:5555:6666:7777:8888
::2222:3333:4444:5555:6666:7777:8888
1111:2222:3333:4444:5555:6666:123.123.123.123
1111:2222:3333:4444:5555::123.123.123.123
1111:2222:3333:4444::123.123.123.123
1111:2222:3333::123.123.123.123
1111:2222::123.123.123.123
1111::123.123.123.123
::123.123.123.123
1111:2222:3333:4444::6666:123.123.123.123
1111:2222:3333::6666:123.123.123.123
1111:2222::6666:123.123.123.123
1111::6666:123.123.123.123
::6666:123.123.123.123
1111:2222:3333::5555:6666:123.123.123.123
1111:2222::5555:6666:123.123.123.123
1111::5555:6666:123.123.123.123
::5555:6666:123.123.123.123
1111:2222::4444:5555:6666:123.123.123.123
1111::4444:5555:6666:123.123.123.123
::4444:5555:6666:123.123.123.123
1111::3333:4444:5555:6666:123.123.123.123
::3333:4444:5555:6666:123.123.123.123
::2222:3333:4444:5555:6666:123.123.123.123
AERON;

// http://home.deds.nl/~aeron/regex/invalid_ipv6.txt
const aeron_invalid_text = <<< 'AERON_INVALID'
# Invalid data
XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX

# To much components
1111:2222:3333:4444:5555:6666:7777:8888:9999
1111:2222:3333:4444:5555:6666:7777:8888::
::2222:3333:4444:5555:6666:7777:8888:9999

# To less components
1111:2222:3333:4444:5555:6666:7777
1111:2222:3333:4444:5555:6666
1111:2222:3333:4444:5555
1111:2222:3333:4444
1111:2222:3333
1111:2222
1111

# Missing :
11112222:3333:4444:5555:6666:7777:8888
1111:22223333:4444:5555:6666:7777:8888
1111:2222:33334444:5555:6666:7777:8888
1111:2222:3333:44445555:6666:7777:8888
1111:2222:3333:4444:55556666:7777:8888
1111:2222:3333:4444:5555:66667777:8888
1111:2222:3333:4444:5555:6666:77778888

# Missing : intended for ::
1111:2222:3333:4444:5555:6666:7777:8888:
1111:2222:3333:4444:5555:6666:7777:
1111:2222:3333:4444:5555:6666:
1111:2222:3333:4444:5555:
1111:2222:3333:4444:
1111:2222:3333:
1111:2222:
1111:
:
:8888
:7777:8888
:6666:7777:8888
:5555:6666:7777:8888
:4444:5555:6666:7777:8888
:3333:4444:5555:6666:7777:8888
:2222:3333:4444:5555:6666:7777:8888
:1111:2222:3333:4444:5555:6666:7777:8888

# :::
:::2222:3333:4444:5555:6666:7777:8888
1111:::3333:4444:5555:6666:7777:8888
1111:2222:::4444:5555:6666:7777:8888
1111:2222:3333:::5555:6666:7777:8888
1111:2222:3333:4444:::6666:7777:8888
1111:2222:3333:4444:5555:::7777:8888
1111:2222:3333:4444:5555:6666:::8888
1111:2222:3333:4444:5555:6666:7777:::

# Double ::
::2222::4444:5555:6666:7777:8888
::2222:3333::5555:6666:7777:8888
::2222:3333:4444::6666:7777:8888
::2222:3333:4444:5555::7777:8888
::2222:3333:4444:5555:7777::8888
::2222:3333:4444:5555:7777:8888::

1111::3333::5555:6666:7777:8888
1111::3333:4444::6666:7777:8888
1111::3333:4444:5555::7777:8888
1111::3333:4444:5555:6666::8888
1111::3333:4444:5555:6666:7777::

1111:2222::4444::6666:7777:8888
1111:2222::4444:5555::7777:8888
1111:2222::4444:5555:6666::8888
1111:2222::4444:5555:6666:7777::

1111:2222:3333::5555::7777:8888
1111:2222:3333::5555:6666::8888
1111:2222:3333::5555:6666:7777::

1111:2222:3333:4444::6666::8888
1111:2222:3333:4444::6666:7777::

1111:2222:3333:4444:5555::7777::

# Invalid data
XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:1.2.3.4
1111:2222:3333:4444:5555:6666:00.00.00.00
1111:2222:3333:4444:5555:6666:000.000.000.000
1111:2222:3333:4444:5555:6666:256.256.256.256

# To much components
1111:2222:3333:4444:5555:6666:7777:8888:1.2.3.4
1111:2222:3333:4444:5555:6666:7777:1.2.3.4
1111:2222:3333:4444:5555:6666::1.2.3.4
::2222:3333:4444:5555:6666:7777:1.2.3.4
1111:2222:3333:4444:5555:6666:1.2.3.4.5

# To less components
1111:2222:3333:4444:5555:1.2.3.4
1111:2222:3333:4444:1.2.3.4
1111:2222:3333:1.2.3.4
1111:2222:1.2.3.4
1111:1.2.3.4
1.2.3.4

# Missing :
11112222:3333:4444:5555:6666:1.2.3.4
1111:22223333:4444:5555:6666:1.2.3.4
1111:2222:33334444:5555:6666:1.2.3.4
1111:2222:3333:44445555:6666:1.2.3.4
1111:2222:3333:4444:55556666:1.2.3.4
1111:2222:3333:4444:5555:66661.2.3.4

# Missing .
1111:2222:3333:4444:5555:6666:255255.255.255
1111:2222:3333:4444:5555:6666:255.255255.255
1111:2222:3333:4444:5555:6666:255.255.255255

# Missing : intended for ::
:1.2.3.4
:6666:1.2.3.4
:5555:6666:1.2.3.4
:4444:5555:6666:1.2.3.4
:3333:4444:5555:6666:1.2.3.4
:2222:3333:4444:5555:6666:1.2.3.4
:1111:2222:3333:4444:5555:6666:1.2.3.4

# :::
:::2222:3333:4444:5555:6666:1.2.3.4
1111:::3333:4444:5555:6666:1.2.3.4
1111:2222:::4444:5555:6666:1.2.3.4
1111:2222:3333:::5555:6666:1.2.3.4
1111:2222:3333:4444:::6666:1.2.3.4
1111:2222:3333:4444:5555:::1.2.3.4

# Double ::
::2222::4444:5555:6666:1.2.3.4
::2222:3333::5555:6666:1.2.3.4
::2222:3333:4444::6666:1.2.3.4
::2222:3333:4444:5555::1.2.3.4

1111::3333::5555:6666:1.2.3.4
1111::3333:4444::6666:1.2.3.4
1111::3333:4444:5555::1.2.3.4

1111:2222::4444::6666:1.2.3.4
1111:2222::4444:5555::1.2.3.4

1111:2222:3333::5555::1.2.3.4

# Missing parts
::.
::..
::...
::1...
::1.2..
::1.2.3.
::.2..
::.2.3.
::.2.3.4
::..3.
::..3.4
::...4

# Extra : in front
:1111:2222:3333:4444:5555:6666:7777::
:1111:2222:3333:4444:5555:6666::
:1111:2222:3333:4444:5555::
:1111:2222:3333:4444::
:1111:2222:3333::
:1111:2222::
:1111::
:::
:1111:2222:3333:4444:5555:6666::8888
:1111:2222:3333:4444:5555::8888
:1111:2222:3333:4444::8888
:1111:2222:3333::8888
:1111:2222::8888
:1111::8888
:::8888
:1111:2222:3333:4444:5555::7777:8888
:1111:2222:3333:4444::7777:8888
:1111:2222:3333::7777:8888
:1111:2222::7777:8888
:1111::7777:8888
:::7777:8888
:1111:2222:3333:4444::6666:7777:8888
:1111:2222:3333::6666:7777:8888
:1111:2222::6666:7777:8888
:1111::6666:7777:8888
:::6666:7777:8888
:1111:2222:3333::5555:6666:7777:8888
:1111:2222::5555:6666:7777:8888
:1111::5555:6666:7777:8888
:::5555:6666:7777:8888
:1111:2222::4444:5555:6666:7777:8888
:1111::4444:5555:6666:7777:8888
:::4444:5555:6666:7777:8888
:1111::3333:4444:5555:6666:7777:8888
:::3333:4444:5555:6666:7777:8888
:::2222:3333:4444:5555:6666:7777:8888
:1111:2222:3333:4444:5555:6666:1.2.3.4
:1111:2222:3333:4444:5555::1.2.3.4
:1111:2222:3333:4444::1.2.3.4
:1111:2222:3333::1.2.3.4
:1111:2222::1.2.3.4
:1111::1.2.3.4
:::1.2.3.4
:1111:2222:3333:4444::6666:1.2.3.4
:1111:2222:3333::6666:1.2.3.4
:1111:2222::6666:1.2.3.4
:1111::6666:1.2.3.4
:::6666:1.2.3.4
:1111:2222:3333::5555:6666:1.2.3.4
:1111:2222::5555:6666:1.2.3.4
:1111::5555:6666:1.2.3.4
:::5555:6666:1.2.3.4
:1111:2222::4444:5555:6666:1.2.3.4
:1111::4444:5555:6666:1.2.3.4
:::4444:5555:6666:1.2.3.4
:1111::3333:4444:5555:6666:1.2.3.4
:::3333:4444:5555:6666:1.2.3.4
:::2222:3333:4444:5555:6666:1.2.3.4

# Extra : at end
1111:2222:3333:4444:5555:6666:7777:::
1111:2222:3333:4444:5555:6666:::
1111:2222:3333:4444:5555:::
1111:2222:3333:4444:::
1111:2222:3333:::
1111:2222:::
1111:::
:::
1111:2222:3333:4444:5555:6666::8888:
1111:2222:3333:4444:5555::8888:
1111:2222:3333:4444::8888:
1111:2222:3333::8888:
1111:2222::8888:
1111::8888:
::8888:
1111:2222:3333:4444:5555::7777:8888:
1111:2222:3333:4444::7777:8888:
1111:2222:3333::7777:8888:
1111:2222::7777:8888:
1111::7777:8888:
::7777:8888:
1111:2222:3333:4444::6666:7777:8888:
1111:2222:3333::6666:7777:8888:
1111:2222::6666:7777:8888:
1111::6666:7777:8888:
::6666:7777:8888:
1111:2222:3333::5555:6666:7777:8888:
1111:2222::5555:6666:7777:8888:
1111::5555:6666:7777:8888:
::5555:6666:7777:8888:
1111:2222::4444:5555:6666:7777:8888:
1111::4444:5555:6666:7777:8888:
::4444:5555:6666:7777:8888:
1111::3333:4444:5555:6666:7777:8888:
::3333:4444:5555:6666:7777:8888:
::2222:3333:4444:5555:6666:7777:8888:
AERON_INVALID;

const all_valid_list = [
'::7',
'::7',
'::11',
'::11',
'::11',
'::',
'25A2:4b2e:48a:2:aaa:4D3:5E:9C44',
'25A2:4b2e:48a::aaaa:4D3:5E:9C44',
'25A2:4b2e:48a:2:aaa:4D3::9C44',
'25A2:4b2e:48a:2:aaa:4D3:5E::',
'25A2:4b2e:48a:2:aaaa:4D3:5E:9C44',
'::',
'1111:2222:3333:4444:5555:6666:7777:8888',
'::',
'1111:2222:3333:4444:5555:6666:7777:8888',
'33::33dd',
'33::33dd',
'1111:2222:3333:4444:5555:6666:7777:8888',
'::333:33',
'33:44:45::',
'33::33dd',
'fffF::',
'::eeee:2:2',
'::eeee',
'::eeee:6:5:4:3:2:1',
'eeee:6::',
'::',
'eeee:6::',
'5:4::3:2:1',
'eeee:6::',
'eeee:6::',
'5:4::3:2:1',
'::',
'f::',
'1111:2222:3333:4444:5555:6666:7777:8888',
'33::33dd',
'33::33dd',
'1111:2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444::6666:7777:8888',
'::6666:7777:8888',
'1111:2222:3333:4444:1:6666:7777:8888',
'4:4:4::6666:7777:8888',
'1::',
'33::33dd',
'33::33dd',
'::',
'::',
'::',
'::',
'::',
'::',
'1111::2222:22:22:22',
'1111::2222:22:22:22',
'::',
'::',
'::',
'::',
'::',
'::',
'::',
'::',
'::',
'::',
'1111::2222:22:22:22',
'1111::2222:22:22:22',
'::',
'::',
'::',
'::',
'1111:2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444:5555:6666:255.255.255.255',
'1111:2222:3333:4444:5555:6666:201.201.201.201',
'1111:2222:3333:4444:5555:6666:200.200.200.200',
'1111:2222:3333:4444:5555:6666:20.20.20.20',
'1111:2222:3333:4444:5555:6666:100.100.100.100',
'1111:2222:3333:4444:5555:6666:99.99.99.99',
'1111:2222:3333:4444:5555:6666:199.199.199.199',
'1111:2222:3333:4444:5555:6666:10.10.10.10',
'1111:2222:3333:4444:5555:6666:0.0.0.0',
'1234:1234:1234:1234:1234:1234:123.231.213.255',
'1234:1234:1234:1234:1234:1234:123.231.213.255',
'::1234:1234:1234:1234:1234:123.231.213.255',
'::1234:1234:1234:1234:1234:123.231.213.255',
'1234:1234::1234:1234:123.231.213.255',
'1234:1234:1234:1234::1234:123.231.213.255',
'1234:1234::1234:1234:1234:123.231.213.255',
'4::22.22.22.22',
'222:222::22:3.3.2.2',
'::192.168.0.1',
'::db0:192.168.0.1',
'db0::192.168.0.1',
'1111:2222:3333:4444:5555:6666:192.64.2.1',
'1111:2222:3333:4444:5555:6666:192.64.2.1',
'1111:2222:3333:4444::6666:192.64.2.1',
'::6666:7777:8888:192.64.2.1',
'1111:2222:3333:4444:1:6666:192.64.2.1',
'4:4:4::6666:192.64.2.1',
'::192.64.2.1',
'1111:2222:3333:4444:5555:6666:192.64.2.1',
'1111:2222:3333:4444::6666:192.64.2.1',
'::192.2.3.4',
'::6666:7777:8888:192.64.2.1',
'1111:2222:3333:4444:1::192.64.2.1',
'4:4:4::6666:192.64.2.1'];

const all_valid_text = <<< 'ALLVALID'
::7
VALID IPv6:
 ::7

 ::11
::11
 ::11

 r::
 25A2:4b2e:48a:2:aaa:4D3:5E:9C44
 25A2:4b2e:48a::aaaa:4D3:5E:9C44
 25A2:4b2e:48a:2:aaa:4D3::9C44

 25A2:4b2e:48a:2:aaa:4D3:5E::
25A2:4b2e:48a:2:aaaa:4D3:5E:9C44
 ::
1111:2222:3333:4444:5555:6666:7777:8888
 ::
1111:2222:3333:4444:5555:6666:7777:8888 33::33dd  33::33dd 1111:2222:3333:4444:5555:6666:7777:8888  ::333:33 33:44:45:: 33::33dd
fffF::
::eeee:2:2
::eeee
::eeee:6:5:4:3:2:1

eeee:6::T
::T
eeee:6::T5:4::3:2:1
eeee:6::T
eeee:6::T5:4::3:2:1

r::
rf::

1111:2222:3333:4444:5555:6666:7777:8888 33::33dd

33::33dd

1111:2222:3333:4444:5555:6666:7777:8888/64 1111:2222:3333:4444:5555:6666:7777:8888/56
 1111:2222:3333:4444::6666:7777:8888/56 ::6666:7777:8888/56
1111:2222:3333:4444:1:6666:7777:8888/56 4:4:4::6666:7777:8888/56

1::

 33::33dd
 33::33dd
 :: ::
 :: :: ::
    :: 1111::2222:22:22:22
  1111::2222:22:22:22

 ::y::
 :: ::

 :: ::
 :: :: ::
    :: 1111::2222:22:22:22
  1111::2222:22:22:22

 ::y::
 :: ::



1111:2222:3333:4444:5555:6666:7777:8888

VALID IPv6+IPv4:
1111:2222:3333:4444:5555:6666:255.255.255.255
1111:2222:3333:4444:5555:6666:201.201.201.201
1111:2222:3333:4444:5555:6666:200.200.200.200
1111:2222:3333:4444:5555:6666:20.20.20.20
1111:2222:3333:4444:5555:6666:100.100.100.100
1111:2222:3333:4444:5555:6666:99.99.99.99
1111:2222:3333:4444:5555:6666:199.199.199.199
1111:2222:3333:4444:5555:6666:10.10.10.10
1111:2222:3333:4444:5555:6666:0.0.0.0
1234:1234:1234:1234:1234:1234:123.231.213.255 1234:1234:1234:1234:1234:1234:123.231.213.255

::1234:1234:1234:1234:1234:123.231.213.255 ::1234:1234:1234:1234:1234:123.231.213.255

1234:1234::1234:1234:123.231.213.255 1234:1234:1234:1234::1234:123.231.213.255

1234:1234::1234:1234:1234:123.231.213.255

4::22.22.22.22 222:222::22:3.3.2.2

::192.168.0.1

::db0:192.168.0.1

db0::192.168.0.1

1111:2222:3333:4444:5555:6666:192.64.2.1/64 1111:2222:3333:4444:5555:6666:192.64.2.1/56
 1111:2222:3333:4444::6666:192.64.2.1,56 ::6666:7777:8888:192.64.2.1/56
1111:2222:3333:4444:1:6666:192.64.2.1/56 x4:4:4::6666:192.64.2.1/56

::192.64.2.1/64 1111:2222:3333:4444:5555:6666:192.64.2.1/56
 1111:2222:3333:4444::6666:192.64.2.1/::192.2.3.4 ::6666:7777:8888:192.64.2.1/56
1111:2222:3333:4444:1::192.64.2.1/56 4:4:4::6666:192.64.2.1/56
ALLVALID;

const all_invalid_text = <<< 'ALLINVALID'
FAIL IPv6+IPv4:
1111:2222:3333:4444:5555:6666:01.01.01.01
1111:2222:3333:4444:5555:6666:00.00.00.00
1111:2222:3333:4444:5555:6666:000.000.000.000
1111:2222:3333:4444:5555:6666:001.001.001.001
1111:2222:3333:4444:5555:6666:000.000.000.000
1111:2222:3333:4444:5555:6666:010.010.010.010
1234:1234:1234:1234:1234:1234:123.231.213.255.1234:1234:1234:1234:1234:1234:123.231.213.255

::1234:1234:1234:1234:1234:123.231.213.255.::1234:1234:1234:1234:1234:123.231.213.255

::1234:1234:1234:1234:1234:1234:123.231.213.255.::1234:1234:1234:1234:1234:1234:123.231.213.255
::1234:1234:1234:1234:1234:1234:123.231.213.255 ::1234:1234:1234:1234:1234:1234:123.231.213.255

::1234:1234:1234:1234:1234:1234:123.231.213.255

FAIL IPv6:
 ::48a:2:aaa:4D3:5E:9C44:3:3

 25A2:4b2e:548a:2:aaa:4D3:5E:9C44:
dddd:ddd:ddd:ddd::ccc:ccc:ccc:cccc:

dddd:ddd:ddd:ddd::ccc:ccc:ccc:cccc:
dddd:ddd:ddd:ddd::ccc:ccc:ccc:cccc:
 33:33dd

 33:33dd
25A2:4b2e:48a:2:e:aaa:4D3:5E:9C44
25A2:4b2e:48a:2:aaa:4D3:5E:9C44:
25A2:4b2e:48a:2:aaa:4D3:4D3:5E::

25A2:4b2e:48a:2:e:aaa:4D3:5E:9C44
25A2:4b2e:48a:2:aaa:4D3:5E:9C44:
25A2:4b2e:48a:2:aaa:4D3:4D3:5E::
::eeee::
::eeee:3::3

::eeee::
::eeee:3::3

 25A2:4b2e:48a:2:aaa:4D3::5E::
33:33dd
25A2:4b2e:48a:2:aaa:4D3:4D3:5E::

eee::eee::ee::ee33:3:33

T5:4:3:2:1


:
 :
 :
 r:
 2:e
 r:e
1:
ø:
1æ:
æ1:
1:p:
p:
1p:
p1:


INVALID IPv6+IPv4:
::eeee:6:T5:4:3:2:1

1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd::.p.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:p:2.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:p:.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:p.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:.

1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.p
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:p.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:p

1111:2222:3333:4444:5555:6666:DDDD:EEEE:4.4.4.4
1111:2222:3333:4444:5555:6666:DDDD:8888:4.4.4.4 33::33dd:
1111:2222:3333:4444:5555:6666:7777:EEEE:4.4.4.4
:5:5:54::
::3333:4444:5555:6666:7777:8888:
ALLINVALID;



const test_content = <<< 'TEST'
::7
IPv6+IPv4:
 ::7
1111:2222:3333:4444:5555:6666:01.01.01.01
1111:2222:3333:4444:5555:6666:00.00.00.00
1111:2222:3333:4444:5555:6666:000.000.000.000
1111:2222:3333:4444:5555:6666:001.001.001.001
1111:2222:3333:4444:5555:6666:000.000.000.000
1111:2222:3333:4444:5555:6666:010.010.010.010
1111:2222:3333:4444:5555:6666:0.0.0.0
1111:2222:3333:4444:5555:6666:00.00.00.00
1111:2222:3333:4444:5555:6666:000.000.000.000
1111:2222:3333:4444:5555:6666:000.000.000.000
1111:2222:3333:4444:5555:6666:123.123.123.123
1111:2222:3333:4444:5555:6666:0.123.123.123
1111:2222:3333:4444:5555:6666:255.255.255.255
1111:2222:3333:4444:5555:6666:201.201.201.201
1111:2222:3333:4444:5555:6666:200.200.200.200
1111:2222:3333:4444:5555:6666:20.20.20.20
1111:2222:3333:4444:5555:6666:100.100.100.100
1111:2222:3333:4444:5555:6666:99.99.99.99
1111:2222:3333:4444:5555:6666:199.199.199.199
1111:2222:3333:4444:5555:6666:10.10.10.10
1111:2222:3333:4444:5555:6666:0.0.0.0
::3333:4444:5555:6666:7777:8888:
::2222:3333:4444:5555:6666:7777:8888:

1234:1234:1234:1234:1234:1234:123.231.213.255.1234:1234:1234:1234:1234:1234:123.231.213.255
1234:1234:1234:1234:1234:1234:123.231.213.255 1234:1234:1234:1234:1234:1234:123.231.213.255

::1234:1234:1234:1234:1234:123.231.213.255.::1234:1234:1234:1234:1234:123.231.213.255
::1234:1234:1234:1234:1234:123.231.213.255 ::1234:1234:1234:1234:1234:123.231.213.255

1234:1234::1234:1234:1234:123.231.213.255.1234::1234:1234:1234:1234:123.231.213.255
1234:1234::1234:1234:123.231.213.255 1234:1234:1234:1234::1234:123.231.213.255

::1234:1234:1234:1234:1234:1234:123.231.213.255.::1234:1234:1234:1234:1234:1234:123.231.213.255
::1234:1234:1234:1234:1234:1234:123.231.213.255 ::1234:1234:1234:1234:1234:1234:123.231.213.255

::1234:1234:1234:1234:1234:1234:123.231.213.255 ::11

1234:1234::1234:1234:1234:123.231.213.255

4::22.22.22.22 222:222::22:3.3.2.2

::192.168.0.1

::db0:192.168.0.1

db0::192.168.0.1

IPv6 only:
 r::
 25A2:4b2e:48a:2:aaa:4D3:5E:9C44
 25A2:4b2e:48a::aaaa:4D3:5E:9C44
 25A2:4b2e:48a:2:aaa:4D3::9C44
 ::48a:2:aaa:4D3:5E:9C44:3:3
 25A2:4b2e:48a:2:aaa:4D3:5E::
 25A2:4b2e:548a:2:aaa:4D3:5E:9C44:
25A2:4b2e:48a:2:aaaa:4D3:5E:9C44
 ::
dddd:ddd:ddd:ddd::ccc:ccc:ccc:cccc:
1111:2222:3333:4444:5555:6666:7777:8888 33:33dd
1111:2222:3333:4444:5555:6666:7777:8888 33::33dd  33::33dd 1111:2222:3333:4444:5555:6666:7777:8888  ::333:33 33:44:45:: 33::33dd
25A2:4b2e:48a:2:e:aaa:4D3:5E:9C44
25A2:4b2e:48a:2:aaa:4D3:5E:9C44:
25A2:4b2e:48a:2:aaa:4D3:4D3:5E::
fffF::
::eeee:2:2
::eeee
::eeee:6:5:4:3:2:1
::eeee::
::eeee:3::3
:: ::
:: :: ::
   :: 1111::2222:22:22:22
 1111::2222:22:22:22

::y::
:: ::

 25A2:4b2e:48a:2:aaa:4D3::5E::
33:33dd
25A2:4b2e:48a:2:aaa:4D3:4D3:5E::
::eeee:6:T5:4:3:2:1

eee::eee::ee::ee33:3:33

eeee:6::T5:4:3:2:1
::T5
eeee:6::T5:4::3:2:1
eeee:6::T5:4:3:2:1
eeee:6::T5:4::3:2:1

r::
rf::

1111:2222:3333:4444:5555:6666:7777:8888 33::33dd

33:33dd

33::33dd

1111:2222:3333:4444:5555:6666:7777:8888/64 1111:2222:3333:4444:5555:6666:7777:8888/56
 1111:2222:3333:4444::6666:7777:8888/56 ::6666:7777:8888/56
1111:2222:3333:4444:1:6666:7777:8888/56 4:4:4::6666:7777:8888/56

1111:2222:3333:4444:5555:6666:192.64.2.1/64 1111:2222:3333:4444:5555:6666:192.64.2.1/56
 1111:2222:3333:4444::6666:192.64.2.1,56 ::6666:7777:8888:192.64.2.1/56
1111:2222:3333:4444:1:6666:192.64.2.1/56 x4:4:4::6666:192.64.2.1/56

::192.64.2.1/64 1111:2222:3333:4444:5555:6666:192.64.2.1/56
 1111:2222:3333:4444::6666:192.64.2.1/::192.2.3.4 ::6666:7777:8888:192.64.2.1/56
1111:2222:3333:4444:1::192.64.2.1/56 4:4:4::6666:192.64.2.1/56

:
 :
 :
 r:
 2:e
 r:e
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd::.p.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:p:2.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:p:.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:p.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.:.
1::
1:
ø:
1æ:
æ1:
1:p:
p:
1p:
p1:

1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:.p
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:p.
1111:2222:3333:4444:5555:6666:7777:8888:4.4.4.4 33::33dd:p
1111:2222:3333:4444:5555:6666:DDDD:EEEE:4.4.4.4 33::33dd
1111:2222:3333:4444:5555:6666:DDDD:8888:4.4.4.4 33::33dd:
1111:2222:3333:4444:5555:6666:7777:EEEE:4.4.4.4 33::33dd
:5:5:54::

1111:2222:3333:4444:5555:6666:7777:8888

::3333:4444:5555:6666:7777:8888:
TEST;

const test_list = [
'::7',
'::7',
'1111:2222:3333:4444:5555:6666:0.0.0.0',
'1111:2222:3333:4444:5555:6666:123.123.123.123',
'1111:2222:3333:4444:5555:6666:0.123.123.123',
'1111:2222:3333:4444:5555:6666:255.255.255.255',
'1111:2222:3333:4444:5555:6666:201.201.201.201',
'1111:2222:3333:4444:5555:6666:200.200.200.200',
'1111:2222:3333:4444:5555:6666:20.20.20.20',
'1111:2222:3333:4444:5555:6666:100.100.100.100',
'1111:2222:3333:4444:5555:6666:99.99.99.99',
'1111:2222:3333:4444:5555:6666:199.199.199.199',
'1111:2222:3333:4444:5555:6666:10.10.10.10',
'1111:2222:3333:4444:5555:6666:0.0.0.0',
'1234:1234:1234:1234:1234:1234:123.231.213.255',
'1234:1234:1234:1234:1234:1234:123.231.213.255',
'::1234:1234:1234:1234:1234:123.231.213.255',
'::1234:1234:1234:1234:1234:123.231.213.255',
'1234:1234::1234:1234:123.231.213.255',
'1234:1234:1234:1234::1234:123.231.213.255',
'::11',
'1234:1234::1234:1234:1234:123.231.213.255',
'4::22.22.22.22',
'222:222::22:3.3.2.2',
'::192.168.0.1',
'::db0:192.168.0.1',
'db0::192.168.0.1',
'::',
'25A2:4b2e:48a:2:aaa:4D3:5E:9C44',
'25A2:4b2e:48a::aaaa:4D3:5E:9C44',
'25A2:4b2e:48a:2:aaa:4D3::9C44',
'25A2:4b2e:48a:2:aaa:4D3:5E::',
'25A2:4b2e:48a:2:aaaa:4D3:5E:9C44',
'::',
'1111:2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444:5555:6666:7777:8888',
'33::33dd',
'33::33dd',
'1111:2222:3333:4444:5555:6666:7777:8888',
'::333:33',
'33:44:45::',
'33::33dd',
'fffF::',
'::eeee:2:2',
'::eeee',
'::eeee:6:5:4:3:2:1',
'::',
'::',
'::',
'::',
'::',
'::',
'1111::2222:22:22:22',
'1111::2222:22:22:22',
'::',
'::',
'::',
'::',
'eeee:6::',
'::',
'eeee:6::',
'5:4::3:2:1',
'eeee:6::',
'eeee:6::',
'5:4::3:2:1',
'::',
'f::',
'1111:2222:3333:4444:5555:6666:7777:8888',
'33::33dd',
'33::33dd',
'1111:2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444:5555:6666:7777:8888',
'1111:2222:3333:4444::6666:7777:8888',
'::6666:7777:8888',
'1111:2222:3333:4444:1:6666:7777:8888',
'4:4:4::6666:7777:8888',
'1111:2222:3333:4444:5555:6666:192.64.2.1',
'1111:2222:3333:4444:5555:6666:192.64.2.1',
'1111:2222:3333:4444::6666:192.64.2.1',
'::6666:7777:8888:192.64.2.1',
'1111:2222:3333:4444:1:6666:192.64.2.1',
'4:4:4::6666:192.64.2.1',
'::192.64.2.1',
'1111:2222:3333:4444:5555:6666:192.64.2.1',
'1111:2222:3333:4444::6666:192.64.2.1',
'::192.2.3.4',
'::6666:7777:8888:192.64.2.1',
'1111:2222:3333:4444:1::192.64.2.1',
'4:4:4::6666:192.64.2.1',
'1::',
'33::33dd',
'33::33dd',
'1111:2222:3333:4444:5555:6666:7777:8888'];

general_debug('Testing Aeron\'s valid text');
test_parse_ipv6(aeron_text, aeron_list, true);

general_debug("\nTesting Aeron's invalid text");
// The 4x:: handles comments!
test_parse_ipv6(aeron_invalid_text, ['::', '::', '::', '::'], true);

general_debug("\nTesting various valid text");
test_parse_ipv6(all_valid_text, all_valid_list, true);

general_debug("\nTesting various invalid text");
test_parse_ipv6(all_invalid_text, [], true);

general_debug("\nTesting various text");
test_parse_ipv6(test_content, test_list, true);
