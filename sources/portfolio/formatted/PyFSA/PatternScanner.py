<!DOCTYPE html PUBLIC "-//IETF//DTD HTML 2.0//EN">
<HTML>
<HEAD>
<TITLE>PatternScanner.py</TITLE>
</HEAD>
<BODY BGCOLOR="#FFFFFF" TEXT="#000000" LINK="#1F00FF" ALINK="#FF0000" VLINK="#9900DD">
<A NAME="top">
<A NAME="file1">
<H1>PyFSA/PatternScanner.py</H1>

<PRE>
<FONT COLOR="#BC8F8F"><B>&quot;&quot;&quot;Module PatternScanner -- methods that construct grammars, and that use themto scan a document for constituentsGrammar compilation-------------------compilePatternGrammar(rules, optimize=0, defaultCategory=None)  Returns an object that can be used as an argument to the parsing  functions. If the optimize argument is true, grammar compilation  takes longer but parsing with the resulting grammar is much faster  (by roughly an order of magnitude).A grammar rule is one of the following:- a Pattern whose action is a CreateConstituentAction enclosing  a category symbol- any other Pattern, if defaultCategory is supplied. (The pattern  is used to create constituents of category defaultCategory, in  this case.)- a pair of a category symbol and a Pattern- or a tuple (cat, fsa), where cat is a ategory string and fsa is an  instance of FSA.FSA.Parsing functions-----------------The following functions all parse a sentence or a set of sentences.parseString and matchingDocumentConstituents return a list ofconstituents. If categories is false, all constituents are returned.Otherwise, only constituents in a category included in categories arereturned.Grammar is a grammar created via one of the grammar compilationfunctions above, and document is a MedlineDocument.parseString(grammar, string, categories=None)  Tokenizes, tags, and stems string, and applies grammar to  it. Returned categories are sorted by decreasing length, so that,  for example, ::  (parseString(GRAMMAR, string, ['reln']) + [None])[0]  returns the longest constituent whose category is 'reln', or None if  there is no such constituent. This function is intended for testing.parseDocument(grammar, document, categories=None)  Applies the grammar to each sentence in document's abstract, and  returns a list of non-preterminal constituents.  (Currently parseString returns *all* constituents in the category  list, and parseDocument returns all *disjoint* categories. I'll  probably break the functionality to look for disjoint categories out  into a separate function, in order to rationalize this.)matchingDocumentSentences(grammar, document, categories)  Returns a list of sentence from document's abstract such that some  constituent in the sentence is of a category in the category list.  This function is intended to be used in grammar debugging, to  compare the set of sentenced matched by one grammar (say, a grammar  that looked for the word 'inhibit') with another (say, a more  complete set of entity/relation patterns, that therefore might miss  some sentences matched by the first grammar).Example--------&gt;&gt;&gt; from Patterns import inhibitor_patterns, inhibit_patterns&gt;&gt;&gt; GRAMMAR = compilePatternGrammar(inhibitor_patterns + inhibit_patterns, defaultCategory='reln')&gt;&gt;&gt; from DocumentParser import DocumentParser&gt;&gt;&gt; parser = DocumentParser('inhibit1.txt')&gt;&gt;&gt; doc = parser.scanDocument()&gt;&gt;&gt; doc.preProcess()&gt;&gt;&gt; print parseDocument(GRAMMAR, doc, ['reln'])&quot;&quot;&quot;</FONT></B>__author__  = <FONT COLOR="#BC8F8F"><B>&quot;Oliver Steele &lt;steele@osteele.com&gt;&quot;</FONT></B><B><FONT COLOR="#A020F0">import</FONT></B> FSA<B><FONT COLOR="#A020F0">import</FONT></B> FSChartParser<B><FONT COLOR="#A020F0">from</FONT></B> FSChartParser <B><FONT COLOR="#A020F0">import</FONT></B> CategorizingAutomaton, Constituent<B><FONT COLOR="#A020F0">from</FONT></B> Matcher <B><FONT COLOR="#A020F0">import</FONT></B> Action<I><FONT COLOR="#B22222">## Tests#class CategoryTest:    isCategoryTest = 1    isConstituentTest = 1        def __init__(self, category):        self.category = category        def __cmp__(self, other):        return cmp(type(self), type(other)) or cmp(self.__class__, other.__class__) or cmp(self.category, other.category)        def __hash__(self):        return hash(self.category)        def __repr__(self):        return str(self.category)        def matches(self, item):        return self.category == getattr(item, 'category', None)        def complement(self):        return AntiCategoryTest([self.category])        def intersection(self, other):        if getattr(other, 'isAntiCategoryTest', 0):            return other.intersection(self)        elif getattr(other, 'isCategoryTest', 0):            if self.category == other.category:                return self            else:                return None        else:            if getattr(other, 'isComplexPatternElement', 0) and other.required.isUnconditional():                return self            else:                return Noneclass AntiCategoryTest:    """Test that a constituent's category IS NOT one of a set of categories."""    isCategoryTest = 1    isAntiCategoryTest = 1    isConstituentTest = 1        def __init__(self, categories):        categories = list(categories)        categories.sort()        self.categories = categories        def __cmp__(self, other):        return cmp(type(self), type(other)) or cmp(self.__class__, other.__class__) or cmp(self.categories, other.categories)        def __hash__(self):        return hash(self.categories)        def __repr__(self):        import string        return string.join(map(lambda c:'~' + c, self.categories), ' & ')        def matches(self, item):        for category in self.categories:            if item.category == category:                return 0        return 1        def complement(self):        return map(CategoryTest, self.categories)        def intersection(self, other):        if getattr(other, 'isAntiCategoryTest', 0):            return AntiCategoryTest(self.categories + filter(lambda e, l=self.categories:e not in l, other.categories))        elif getattr(other, 'isCategoryTest', 0):            if other.category in self.categories:                return None            return other        else:            return other## Parser classes#class ScannerCategorizingAutomaton(CategorizingAutomaton):    def prime(self):        self.buildDecisionFunctions()        self.stateMatchesConstituents(self.initialState)        #    # Decision trees    #    def buildDecisionFunction(self, state):        pairs = map(lambda (_, state, test):(test, state), self.transitionsFrom(state))        tokenTestPairs = filter(lambda (test, state):getattr(test, 'isPatternElement', 0), pairs)        constituentTestPairs = filter(lambda (test, state):not getattr(test, 'isPatternElement', 0), pairs)        tokenTestDecider = tokenTestPairs and self.buildDecisionTreeDecider(tokenTestPairs)        constituentTestDecider = constituentTestPairs and self.buildSerialDecider(constituentTestPairs)        if tokenTestDecider and constituentTestDecider:            def multiplexorDecisionFunction(constituent, tokenTestDecider=tokenTestDecider, constituentTestDecider=constituentTestDecider):                if hasattr(constituent, 'token'):                    return tokenTestDecider(constituent)                else:                    return constituentTestDecider(constituent)            return multiplexorDecisionFunction        elif tokenTestDecider:            return lambda constituent, fn=tokenTestDecider: hasattr(constituent, 'token') and fn(constituent)        else:            return constituentTestDecider or self.buildSerialDecider(constituentTestPairs)        #    # Accepting    #    def labelMatches(self, label, constituent):        if getattr(label, 'isPatternElement', 0):            return hasattr(constituent, 'token') and label.match(constituent)        else:            return label.matches(constituent)        def computeStateMatchesConstituents(self, state):        for _, _, test in self.transitionsFrom(state):            if getattr(test, 'isConstituentTest', 0):                return 1        return 0        #    # Presentation template overrides    #    def tokenSeparator(self):        return '  '## Pattern compilation#class CreateConstituentAction(Action):    def __init__(self, cat):        self.cat = cat        def apply(self, children, start, end):        return Constituent(self.cat, children, start, end)def compilePatternElement(element, sourceLocation=None):    """Compile a PatternElement to a (possibly nondeterministic) FSA."""    if getattr(element, 'isPatternElement', 0):        fsa = FSA.singleton(element.withoutQuantifier(), arcMetadata=sourceLocation)        if element.isKleeneStar():            fsa = FSA.closure(fsa)        elif element.isKleenePlus():            fsa = FSA.iteration(fsa)        elif element.isOptional():            fsa = FSA.option(fsa)        return fsa    return FSA.singleton(element, arcMetadata=sourceLocation)def compilePatternElements(elements, pattern=None):    import string    arcs = []    sourceLocation = None    for index, element in map(None, range(len(elements)), elements):        if pattern is not None:             sourceLocation = [(pattern, index)]        arcs.append(compilePatternElement(element, sourceLocation))    fsa = reduce(FSA.concatenation, arcs).minimized()    fsa.label = string.join(map(str, elements))    return fsadef compilePattern(pattern):    """Compile a Pattern to a minimal FSA that detects sequences that    match the pattern."""    fsa = compilePatternElements(pattern.elements).coerce(ScannerCategorizingAutomaton)    fsa.label = pattern.name    fsa.source = pattern    return fsa## Grammar compilation#def compilePatternRule(rule, defaultCategory=None):    from FSChartParser import compileRule    from types import TupleType    if getattr(rule, 'isCategorizingAutomaton', 0):        automaton = rule    elif type(rule) == TupleType:        lhs, rhs = rule        automaton = rhs.coerce(ScannerCategorizingAutomaton).minimized()        automaton.setFinalCategory(lhs)    else:        category = getattr(rule.action, 'cat', defaultCategory)        assert category, "each grammar pattern needs a CreateConstituentAction, or defaultCategory must be supplied"        automaton = compilePattern(rule).minimized()        automaton.setFinalCategory(category)    return automatondef compilePatternGrammar(rules, optimize=0, defaultCategory=None):    """Return a list of (lhs, automaton) pairs, suitable for use as an    FSChartParser grammar."""    from FSChartParser import compileRules, combineRules    rules = compileRules(map(lambda rule, category=defaultCategory:compilePatternRule(rule, category), rules))    if optimize:        rules = [combineRules(rules, lambda category:CategoryTest('=>' + category))]    for rule in rules:        rule.prime()    return rules## Scanning functions#def tokenizeSentence(text):    from ntokenizer import tokenize    from sentensifier import sentensify    from ourtagger import tag    from htagger import desambiguate    from stemmer import stemSentence    from DocumentModel import Sentence    tokens = tokenize(text)    tokens = desambiguate(tag(tokens))    sentence = Sentence(tokens)    stemSentence(sentence)    return sentencedef parseSentence(grammar, sentence, categories=None):    return FSChartParser.ChartParser(grammar).parse(sentence).constituents(categories)def parseString(grammar, text, categories=None):    return parseSentence(grammar, tokenizeSentence(text), categories)def parseDocument(grammar, document, categories=None):    """Use grammar to parse the sentences in document, and return the    maximal disjoint constituents in each sentence.  Categories    defaults to None (no restriction), and fn defaults to a function    that prints the constituents to standard output.        document.preProcess() must have been called."""    parser = FSChartParser.ChartParser(grammar)    constituents = []    for sentence in document:        parser.parse(sentence)        constituents.extend(parser.constituents(categories))    return constituentsdef matchingDocumentSentences(grammar, document, categories=None):    """Use grammar to parse the sentences in document, and return    sentences that contained a category in categories, or any    nonterminal at all if categories is false.        document.preProcess() must have been called."""    parser = FSChartParser.ChartParser(grammar)    sentences = []    for sentence in document:        parser.parse(sentence)        if parser.constituents(categories):            sentences.append(sentence)    return sentence</FONT></I></PRE>
<HR>
<ADDRESS>Generated by <A HREF="http://www.iki.fi/~mtr/genscript/">GNU enscript 1.6.1</A>.</ADDRESS>
</BODY>
</HTML>
