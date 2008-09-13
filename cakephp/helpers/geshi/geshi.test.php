<?php
App::import('Helper', 'Geshi');

class geshiTestCase extends CakeTestCase {
	
	function setUp() {
		ClassRegistry::flush();
	}
	function startTest() {
		$this->geshi = new GeshiHelper();
	}
	
	function testHighlight() {
		$this->geshi->showPlainTextButton = false;
		//simple one code block
		$text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
		$result = $this->geshi->highlight($text);
		$expected = '<p>This is some text</p><div class="code" lang="php"><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$foo</span> = <span class="st0">&quot;foo&quot;</span>; <span class="kw2">?&gt;</span></div></li></ol></div><p>More text</p>';
		$this->assertEqual($result, $expected);
		
		//two code blocks
		$text = '<p>Some text</p><pre lang="php"><?php echo $foo; ?></pre><pre lang="php"><?php echo $bar; ?></pre><p>Even more text</p>';
		$result = $this->geshi->highlight($text);
		$expected = '<p>Some text</p><div class="code" lang="php"><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$foo</span>; <span class="kw2">?&gt;</span></div></li></ol></div><div class="code" lang="php"><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$bar</span>; <span class="kw2">?&gt;</span></div></li></ol></div><p>Even more text</p>';
		$this->assertEqual($result, $expected);
		
		//three code blocks.
		$text = '<pre lang="php"><?php echo bar;?></pre><pre lang="python">print fooBar</pre><pre lang="javascript">alert("myTest"); </pre>';
	 	$result = $this->geshi->highlight($text);
		$expected = '<div class="code" lang="php"><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> bar;?&gt;</div></li></ol></div><div class="code" lang="python"><ol><li class="li1"><div class="de1"><span class="kw1">print</span> fooBar</div></li></ol></div><div class="code" lang="javascript"><ol><li class="li1"><div class="de1"><span class="kw3">alert</span><span class="br0">&#40;</span><span class="st0">&quot;myTest&quot;</span><span class="br0">&#41;</span>;</div></li></ol></div>';
		$this->assertEqual($expected, $result);
	
		//codeblock with single quotes
		$text = '<pre lang=\'php\'><?php echo $foo = "foo"; ?></pre>';
		$result = $this->geshi->highlight($text);
		$expected = '<div class="code" lang=\'php\'><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$foo</span> = <span class="st0">&quot;foo&quot;</span>; <span class="kw2">?&gt;</span></div></li></ol></div>';
		$this->assertEqual($result, $expected);
			
		
		//more than one valid code block container
		$this->geshi->validContainers = array('pre', 'code');
		$text = '<pre lang="php"><?php echo $foo = "foo"; ?></pre><p>Text</p><code lang="php">echo $foo = "foo";</code>';
		$result = $this->geshi->highlight($text);
		$expected = '<div class="code" lang="php"><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$foo</span> = <span class="st0">&quot;foo&quot;</span>; <span class="kw2">?&gt;</span></div></li></ol></div><p>Text</p><code lang="php"><ol><li class="li1"><div class="de1"><a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$foo</span> = <span class="st0">&quot;foo&quot;</span>;</div></li></ol></code>';
		$this->assertEqual($result, $expected);
		
		// No valid languages no highlights
		$this->geshi->validContainers = array('pre');
		$this->geshi->validLanguages = array();
		$text = '<p>text</p><pre lang="php">echo $foo;</pre><p>text</p>';
		$result = $this->geshi->highlight($text);
		$expected = '<p>text</p><div class="code" lang="php">echo $foo;</div><p>text</p>';
		$this->assertEqual($result, $expected);			
	}
	
	function testPlainTextButton() {
		//simple one code block
		$text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
		$result = $this->geshi->highlight($text);
		$expected = '<p>This is some text</p><a href="#null" class="geshi-plain-text">Show Plain Text</a><div class="code" lang="php"><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$foo</span> = <span class="st0">&quot;foo&quot;</span>; <span class="kw2">?&gt;</span></div></li></ol></div><p>More text</p>';
		$this->assertEqual($result, $expected);
	}
	
	function testNoTagReplacement() {
			//simple one code block
			$this->geshi->showPlainTextButton = false;
			$this->geshi->containerMap = array();
			
			$text = '<p>This is some text</p><pre lang="php"><?php echo $foo = "foo"; ?></pre><p>More text</p>';
			$result = $this->geshi->highlight($text);
			$expected = '<p>This is some text</p><pre lang="php"><ol><li class="li1"><div class="de1"><span class="kw2">&lt;?php</span> <a href="http://www.php.net/echo"><span class="kw3">echo</span></a> <span class="re0">$foo</span> = <span class="st0">&quot;foo&quot;</span>; <span class="kw2">?&gt;</span></div></li></ol></pre><p>More text</p>';
			$this->assertEqual($result, $expected);
	}
	
	function endTest() {
		unset($this->geshi);
	}
	
}
?>