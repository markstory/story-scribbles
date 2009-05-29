<?php
/* $Id: vcf.test.php 3 2008-11-10 17:57:48Z ehomanchuk@fosterinteractive.com $ */
/**
 *
 * VCF Helper test.
 *
 * @lastChanged  $Date: 2008-11-10 12:57:48 -0500 (Mon, 10 Nov 2008) $
 * @Revision  $Rev: 3 $
 */

App::import('Helper', 'Vcf');

class VcfHelperTestCase extends CakeTestCase {

	function setUp() {
		$this->vcf = new VcfHelper();
	}

	function testEndBegin() {
		$result = $this->vcf->begin();
		$this->assertEqual($result, "BEGIN:VCARD\n");

		$result = $this->vcf->end();
		$this->assertEqual($result, "END:VCARD\n");
	}
/**
 * test element creation
 *
 */
	function testElement() {
		$result = $this->vcf->attr('fullName', 'Mark Story');
		$expected = "FN:Mark Story\n";
		$this->assertEqual($result, $expected);

		$result = $this->vcf->attr('cellPhone', '555-666-6666');
		$expected = "TEL;CELL:555-666-6666\n";
		$this->assertEqual($result, $expected);

		$result = $this->vcf->attr('organization', 'mark-story');
		$expected = "ORG:mark-story\n";
		$this->assertEqual($result, $expected);

		$result = $this->vcf->attr('cellPhone', '');
		$expected = "";
		$this->assertEqual($result, $expected);

		$result = $this->vcf->attr('timezone', '-05:00');
		$expected = "TZ:-05\:00\n";
		$this->assertEqual($result, $expected);

		$result = $this->vcf->attr('email', 'example@example.com');
		$expected = "EMAIL;INTERNET:example@example.com\n";
		$this->assertEqual($result, $expected);
	}
/**
 * test __call method.
 *
 * @return void
 **/
	function testCall() {
		$result = $this->vcf->name(array('first' => 'bob', 'last' => 'smith', 'middle' => 'J'));

		$result = $this->vcf->fullName('Mark Story');
		$expected = "FN:Mark Story\n";
		$this->assertEqual($result, $expected);

		$this->expectError();
		$this->vcf->stupidMethodName('foobar');
	}
/**
 * Test Address
 *
 */
	function testAddress() {
		$result = $this->vcf->address('home', array(
			'province' => 'Ontario',
			'postal' => 'M1M 1M1',
			'city' => 'Toronto',
			'country' => 'Canada',
			'street' => '555 somestreet rd.'
		));
		$expected = "ADR;HOME:;;555 somestreet rd.;Toronto;Ontario;M1M 1M1;Canada;\n" .
					"LABEL;POSTAL;HOME;ENCODING=QUOTED-PRINTABLE:555 somestreet rd.=0D=0AToronto, Ontario M1M 1M1=0D=0ACanada\n";
		$this->assertEqual($result, $expected);


		$result = $this->vcf->address('home', array(
			'province' => 'Ontario',
			'city' => 'Toronto',
			'country' => 'Canada',
			'street' => '555 somestreet rd.'
		));
		$expected = "ADR;HOME:;;555 somestreet rd.;Toronto;Ontario;;Canada;\n" .
					"LABEL;POSTAL;HOME;ENCODING=QUOTED-PRINTABLE:555 somestreet rd.=0D=0AToronto, Ontario =0D=0ACanada\n";
		$this->assertEqual($result, $expected);
	}

	function tearDown() {
		unset($this->vcf);
	}
}
?>