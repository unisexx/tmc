@extends('layouts.main')

@section('title', 'TinyMCE')
@section('breadcrumb-item', 'Forms')

@section('breadcrumb-item-active', 'TinyMCE')

@section('css')
@endsection

@section('content')
    <div class="row">
        <!-- [ form-element ] start -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5>Basic Demo</h5>
                </div>
                <div class="card-body">
                    <textarea id="pc-tinymce-1" name="pc-tinymce-1" class="tox-target">
                <p><img style="display: block; margin-left: auto; margin-right: auto;" title="Tiny Logo" src="https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png" alt="TinyMCE Logo" width="128" height="128"></p>
                <h2 style="text-align: center;">Welcome to the TinyMCE Cloud demo!</h2>
                
                <h5 style="text-align: center;">This demo includes <em>enterprise</em>, also known as <em>Premium</em> features.</h5>
                
                <h5 style="text-align: center;">Try out these features as provided in this full featured example.</h5>
                
                <h5 style="text-align: center;">And visit the <a href="https://www.tiny.cloud/pricing">pricing page</a> to learn more about our Premium plugins.</h5>
                
                <h2>Got questions or need help?</h2>
                
                <ul>
                  <li>Our <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/">documentation</a> is a great resource for learning how to configure TinyMCE.</li>
                  <li>Have a specific question? Try the <a href="https://stackoverflow.com/questions/tagged/tinymce" target="_blank" rel="noopener"><code>tinymce</code> tag at Stack Overflow</a>.</li>
                  <li>We also offer enterprise grade support as part of <a href="https://www.tiny.cloud/pricing">TinyMCE premium subscriptions</a>.</li>
                </ul>
                
                <h2>A simple table to play with</h2>
                
                  <table style="border-collapse: collapse; width: 100%;" border="1">
                    <thead>
                      <tr style="text-align: left;">
                        <th>Product</th>
                        <th>Cost</th>
                        <th>Really?</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>TinyMCE Cloud</td>
                        <td>Get started for free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                      <tr>
                        <td>Plupload</td>
                        <td>Free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                    </tbody>
                  </table>
                
                
                <h2>Character strings to demonstrate some of the Advanced Typography plugin’s features</h2>
                
                <p>Select the characters in the list below and choose <strong>Tools → Typography → Enhance</strong>.</p>
                
                <ul style="list-style-type: none;">
                  <li>Not really an arrow glyph: -></li>
                  <li>"Double tear-drop quotation marks and apostrophes aren't typographically elegant."</li>
                  <li>But they should be honored in a <code>code-fragment: "true"</code>.</li>
                  <li>(c) symbol</li>
                  <li>(tm) symbol</li>
                  <li>30C is 86F</li>
                </ul>
                
                <h2 class="p1"><span class="s1">🤖</span><span class="s2"><strong> Try out AI Assistant!</strong></span></h2>
                
                <p class="p2"><span class="s2">Below are just a few of the ways you can use AI Assistant within your app. Since you can define your own custom prompts, the sky really is the limit!</span></p>
                <p class="p2"><span class="s2"><strong>&nbsp;</strong></span><span class="s3">🎭</span><span class="s2"><strong> Changing tone </strong>&ndash;<strong>&nbsp;</strong>Lighten up the sentence below by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Change tone &gt; Friendly</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">The 3Q23 financial results followed a predictable trend, reflecting the status quo from previous years.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">📝</span><span class="s2"><strong> Summarizing&nbsp;</strong>&ndash; Below is a long paragraph that people may not want to read from start to finish. Get a quick summary by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Summarize content</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">Population growth in the 17th century was marked by significant increment in the number of people around the world. Various factors contributed to this demographic trend. Firstly, advancements in agriculture and technology resulted in increased food production and improved living conditions. This led to decreased mortality rates and better overall health, allowing for more individuals to survive and thrive. Additionally, the exploration and expansion of European powers, such as colonization efforts, fostered migration and settlement in new territories.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">💡</span><span class="s2"><strong> Writing from scratch</strong> &ndash; Ask AI Assistant to generate content from scratch by clicking <img src="../_images/ai-plugin/ai-icon.svg" width="20" height="20"/>, and typing&nbsp;<em>Write a marketing email announcing TinyMCE's new AI Assistant plugin</em>.</span></p>
                <p class="p2">&nbsp;</p>
                
                <h2>Note on the included Templates demonstration</h2>
                
                <p>The included Templates demonstration uses the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/#advtemplate_list"><code>advtemplate_list</code></a> configuration option to return a local promise containing a basic Template structure with self-contained examples.</p>
                
                <p>This example allows for the loading of and interacting with the Template user-interface but cannot be used to create, edit, or save Template items.</p>
                
                <p>See the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/">Templates</a> documentation for details on how to setup Templates to interact with external data sources.</p>
                
                <h2>Found a bug?</h2>
                
                <p>If you think you have found a bug please create an issue on the <a href="https://github.com/tinymce/tinymce/issues">GitHub repo</a> to report it to the developers.</p>
                
                <h2>Finally…</h2>
                
                <p>Don’t forget to check out our other product <a href="http://plupload.com" target="_blank">Plupload</a>, your ultimate upload solution featuring HTML5 upload support.</p>
                
                <p>Thanks for supporting TinyMCE! We hope it helps you and your users create great content.</p>
                
                <p>All the best from the TinyMCE team.</p>
                      </textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5>TinyMCE with Toolbar</h5>
                </div>
                <div class="card-body">
                    <textarea id="pc-tinymce-2" name="pc-tinymce-2" class="tox-target">
                <p><img style="display: block; margin-left: auto; margin-right: auto;" title="Tiny Logo" src="https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png" alt="TinyMCE Logo" width="128" height="128"></p>
                <h2 style="text-align: center;">Welcome to the TinyMCE Cloud demo!</h2>
                
                <h5 style="text-align: center;">This demo includes <em>enterprise</em>, also known as <em>Premium</em> features.</h5>
                
                <h5 style="text-align: center;">Try out these features as provided in this full featured example.</h5>
                
                <h5 style="text-align: center;">And visit the <a href="https://www.tiny.cloud/pricing">pricing page</a> to learn more about our Premium plugins.</h5>
                
                <h2>Got questions or need help?</h2>
                
                <ul>
                  <li>Our <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/">documentation</a> is a great resource for learning how to configure TinyMCE.</li>
                  <li>Have a specific question? Try the <a href="https://stackoverflow.com/questions/tagged/tinymce" target="_blank" rel="noopener"><code>tinymce</code> tag at Stack Overflow</a>.</li>
                  <li>We also offer enterprise grade support as part of <a href="https://www.tiny.cloud/pricing">TinyMCE premium subscriptions</a>.</li>
                </ul>
                
                <h2>A simple table to play with</h2>
                
                  <table style="border-collapse: collapse; width: 100%;" border="1">
                    <thead>
                      <tr style="text-align: left;">
                        <th>Product</th>
                        <th>Cost</th>
                        <th>Really?</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>TinyMCE Cloud</td>
                        <td>Get started for free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                      <tr>
                        <td>Plupload</td>
                        <td>Free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                    </tbody>
                  </table>
                
                
                <h2>Character strings to demonstrate some of the Advanced Typography plugin’s features</h2>
                
                <p>Select the characters in the list below and choose <strong>Tools → Typography → Enhance</strong>.</p>
                
                <ul style="list-style-type: none;">
                  <li>Not really an arrow glyph: -></li>
                  <li>"Double tear-drop quotation marks and apostrophes aren't typographically elegant."</li>
                  <li>But they should be honored in a <code>code-fragment: "true"</code>.</li>
                  <li>(c) symbol</li>
                  <li>(tm) symbol</li>
                  <li>30C is 86F</li>
                </ul>
                
                <h2 class="p1"><span class="s1">🤖</span><span class="s2"><strong> Try out AI Assistant!</strong></span></h2>
                
                <p class="p2"><span class="s2">Below are just a few of the ways you can use AI Assistant within your app. Since you can define your own custom prompts, the sky really is the limit!</span></p>
                <p class="p2"><span class="s2"><strong>&nbsp;</strong></span><span class="s3">🎭</span><span class="s2"><strong> Changing tone </strong>&ndash;<strong>&nbsp;</strong>Lighten up the sentence below by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Change tone &gt; Friendly</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">The 3Q23 financial results followed a predictable trend, reflecting the status quo from previous years.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">📝</span><span class="s2"><strong> Summarizing&nbsp;</strong>&ndash; Below is a long paragraph that people may not want to read from start to finish. Get a quick summary by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Summarize content</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">Population growth in the 17th century was marked by significant increment in the number of people around the world. Various factors contributed to this demographic trend. Firstly, advancements in agriculture and technology resulted in increased food production and improved living conditions. This led to decreased mortality rates and better overall health, allowing for more individuals to survive and thrive. Additionally, the exploration and expansion of European powers, such as colonization efforts, fostered migration and settlement in new territories.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">💡</span><span class="s2"><strong> Writing from scratch</strong> &ndash; Ask AI Assistant to generate content from scratch by clicking <img src="../_images/ai-plugin/ai-icon.svg" width="20" height="20"/>, and typing&nbsp;<em>Write a marketing email announcing TinyMCE's new AI Assistant plugin</em>.</span></p>
                <p class="p2">&nbsp;</p>
                
                <h2>Note on the included Templates demonstration</h2>
                
                <p>The included Templates demonstration uses the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/#advtemplate_list"><code>advtemplate_list</code></a> configuration option to return a local promise containing a basic Template structure with self-contained examples.</p>
                
                <p>This example allows for the loading of and interacting with the Template user-interface but cannot be used to create, edit, or save Template items.</p>
                
                <p>See the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/">Templates</a> documentation for details on how to setup Templates to interact with external data sources.</p>
                
                <h2>Found a bug?</h2>
                
                <p>If you think you have found a bug please create an issue on the <a href="https://github.com/tinymce/tinymce/issues">GitHub repo</a> to report it to the developers.</p>
                
                <h2>Finally…</h2>
                
                <p>Don’t forget to check out our other product <a href="http://plupload.com" target="_blank">Plupload</a>, your ultimate upload solution featuring HTML5 upload support.</p>
                
                <p>Thanks for supporting TinyMCE! We hope it helps you and your users create great content.</p>
                
                <p>All the best from the TinyMCE team.</p>
                      </textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5>TinyMCE with Plugins</h5>
                </div>
                <div class="card-body">
                    <textarea id="pc-tinymce-3" name="pc-tinymce-3" class="tox-target">
                <p><img style="display: block; margin-left: auto; margin-right: auto;" title="Tiny Logo" src="https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png" alt="TinyMCE Logo" width="128" height="128"></p>
                <h2 style="text-align: center;">Welcome to the TinyMCE Cloud demo!</h2>
                
                <h5 style="text-align: center;">This demo includes <em>enterprise</em>, also known as <em>Premium</em> features.</h5>
                
                <h5 style="text-align: center;">Try out these features as provided in this full featured example.</h5>
                
                <h5 style="text-align: center;">And visit the <a href="https://www.tiny.cloud/pricing">pricing page</a> to learn more about our Premium plugins.</h5>
                
                <h2>Got questions or need help?</h2>
                
                <ul>
                  <li>Our <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/">documentation</a> is a great resource for learning how to configure TinyMCE.</li>
                  <li>Have a specific question? Try the <a href="https://stackoverflow.com/questions/tagged/tinymce" target="_blank" rel="noopener"><code>tinymce</code> tag at Stack Overflow</a>.</li>
                  <li>We also offer enterprise grade support as part of <a href="https://www.tiny.cloud/pricing">TinyMCE premium subscriptions</a>.</li>
                </ul>
                
                <h2>A simple table to play with</h2>
                
                  <table style="border-collapse: collapse; width: 100%;" border="1">
                    <thead>
                      <tr style="text-align: left;">
                        <th>Product</th>
                        <th>Cost</th>
                        <th>Really?</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>TinyMCE Cloud</td>
                        <td>Get started for free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                      <tr>
                        <td>Plupload</td>
                        <td>Free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                    </tbody>
                  </table>
                
                
                <h2>Character strings to demonstrate some of the Advanced Typography plugin’s features</h2>
                
                <p>Select the characters in the list below and choose <strong>Tools → Typography → Enhance</strong>.</p>
                
                <ul style="list-style-type: none;">
                  <li>Not really an arrow glyph: -></li>
                  <li>"Double tear-drop quotation marks and apostrophes aren't typographically elegant."</li>
                  <li>But they should be honored in a <code>code-fragment: "true"</code>.</li>
                  <li>(c) symbol</li>
                  <li>(tm) symbol</li>
                  <li>30C is 86F</li>
                </ul>
                
                <h2 class="p1"><span class="s1">🤖</span><span class="s2"><strong> Try out AI Assistant!</strong></span></h2>
                
                <p class="p2"><span class="s2">Below are just a few of the ways you can use AI Assistant within your app. Since you can define your own custom prompts, the sky really is the limit!</span></p>
                <p class="p2"><span class="s2"><strong>&nbsp;</strong></span><span class="s3">🎭</span><span class="s2"><strong> Changing tone </strong>&ndash;<strong>&nbsp;</strong>Lighten up the sentence below by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Change tone &gt; Friendly</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">The 3Q23 financial results followed a predictable trend, reflecting the status quo from previous years.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">📝</span><span class="s2"><strong> Summarizing&nbsp;</strong>&ndash; Below is a long paragraph that people may not want to read from start to finish. Get a quick summary by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Summarize content</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">Population growth in the 17th century was marked by significant increment in the number of people around the world. Various factors contributed to this demographic trend. Firstly, advancements in agriculture and technology resulted in increased food production and improved living conditions. This led to decreased mortality rates and better overall health, allowing for more individuals to survive and thrive. Additionally, the exploration and expansion of European powers, such as colonization efforts, fostered migration and settlement in new territories.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">💡</span><span class="s2"><strong> Writing from scratch</strong> &ndash; Ask AI Assistant to generate content from scratch by clicking <img src="../_images/ai-plugin/ai-icon.svg" width="20" height="20"/>, and typing&nbsp;<em>Write a marketing email announcing TinyMCE's new AI Assistant plugin</em>.</span></p>
                <p class="p2">&nbsp;</p>
                
                <h2>Note on the included Templates demonstration</h2>
                
                <p>The included Templates demonstration uses the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/#advtemplate_list"><code>advtemplate_list</code></a> configuration option to return a local promise containing a basic Template structure with self-contained examples.</p>
                
                <p>This example allows for the loading of and interacting with the Template user-interface but cannot be used to create, edit, or save Template items.</p>
                
                <p>See the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/">Templates</a> documentation for details on how to setup Templates to interact with external data sources.</p>
                
                <h2>Found a bug?</h2>
                
                <p>If you think you have found a bug please create an issue on the <a href="https://github.com/tinymce/tinymce/issues">GitHub repo</a> to report it to the developers.</p>
                
                <h2>Finally…</h2>
                
                <p>Don’t forget to check out our other product <a href="http://plupload.com" target="_blank">Plupload</a>, your ultimate upload solution featuring HTML5 upload support.</p>
                
                <p>Thanks for supporting TinyMCE! We hope it helps you and your users create great content.</p>
                
                <p>All the best from the TinyMCE team.</p>
                      </textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5>TinyMCE with All features</h5>
                </div>
                <div class="card-body">
                    <textarea id="pc-tinymce-4" name="pc-tinymce-4" class="tox-target">
                <p><img style="display: block; margin-left: auto; margin-right: auto;" title="Tiny Logo" src="https://www.tiny.cloud/docs/images/logos/android-chrome-256x256.png" alt="TinyMCE Logo" width="128" height="128"></p>
                <h2 style="text-align: center;">Welcome to the TinyMCE Cloud demo!</h2>
                
                <h5 style="text-align: center;">This demo includes <em>enterprise</em>, also known as <em>Premium</em> features.</h5>
                
                <h5 style="text-align: center;">Try out these features as provided in this full featured example.</h5>
                
                <h5 style="text-align: center;">And visit the <a href="https://www.tiny.cloud/pricing">pricing page</a> to learn more about our Premium plugins.</h5>
                
                <h2>Got questions or need help?</h2>
                
                <ul>
                  <li>Our <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/">documentation</a> is a great resource for learning how to configure TinyMCE.</li>
                  <li>Have a specific question? Try the <a href="https://stackoverflow.com/questions/tagged/tinymce" target="_blank" rel="noopener"><code>tinymce</code> tag at Stack Overflow</a>.</li>
                  <li>We also offer enterprise grade support as part of <a href="https://www.tiny.cloud/pricing">TinyMCE premium subscriptions</a>.</li>
                </ul>
                
                <h2>A simple table to play with</h2>
                
                  <table style="border-collapse: collapse; width: 100%;" border="1">
                    <thead>
                      <tr style="text-align: left;">
                        <th>Product</th>
                        <th>Cost</th>
                        <th>Really?</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>TinyMCE Cloud</td>
                        <td>Get started for free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                      <tr>
                        <td>Plupload</td>
                        <td>Free</td>
                        <td><strong>Yes!</strong></td>
                      </tr>
                    </tbody>
                  </table>
                
                
                <h2>Character strings to demonstrate some of the Advanced Typography plugin’s features</h2>
                
                <p>Select the characters in the list below and choose <strong>Tools → Typography → Enhance</strong>.</p>
                
                <ul style="list-style-type: none;">
                  <li>Not really an arrow glyph: -></li>
                  <li>"Double tear-drop quotation marks and apostrophes aren't typographically elegant."</li>
                  <li>But they should be honored in a <code>code-fragment: "true"</code>.</li>
                  <li>(c) symbol</li>
                  <li>(tm) symbol</li>
                  <li>30C is 86F</li>
                </ul>
                
                <h2 class="p1"><span class="s1">🤖</span><span class="s2"><strong> Try out AI Assistant!</strong></span></h2>
                
                <p class="p2"><span class="s2">Below are just a few of the ways you can use AI Assistant within your app. Since you can define your own custom prompts, the sky really is the limit!</span></p>
                <p class="p2"><span class="s2"><strong>&nbsp;</strong></span><span class="s3">🎭</span><span class="s2"><strong> Changing tone </strong>&ndash;<strong>&nbsp;</strong>Lighten up the sentence below by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Change tone &gt; Friendly</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">The 3Q23 financial results followed a predictable trend, reflecting the status quo from previous years.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">📝</span><span class="s2"><strong> Summarizing&nbsp;</strong>&ndash; Below is a long paragraph that people may not want to read from start to finish. Get a quick summary by selecting the text, clicking <img src="../_images/ai-plugin/wand-icon.svg" width="20" height="20"/>,&nbsp;and choosing <em>Summarize content</em>.</span></p>
                <blockquote>
                <p class="p2"><span class="s2">Population growth in the 17th century was marked by significant increment in the number of people around the world. Various factors contributed to this demographic trend. Firstly, advancements in agriculture and technology resulted in increased food production and improved living conditions. This led to decreased mortality rates and better overall health, allowing for more individuals to survive and thrive. Additionally, the exploration and expansion of European powers, such as colonization efforts, fostered migration and settlement in new territories.</span></p>
                </blockquote>
                <p class="p2"><span class="s3">💡</span><span class="s2"><strong> Writing from scratch</strong> &ndash; Ask AI Assistant to generate content from scratch by clicking <img src="../_images/ai-plugin/ai-icon.svg" width="20" height="20"/>, and typing&nbsp;<em>Write a marketing email announcing TinyMCE's new AI Assistant plugin</em>.</span></p>
                <p class="p2">&nbsp;</p>
                
                <h2>Note on the included Templates demonstration</h2>
                
                <p>The included Templates demonstration uses the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/#advtemplate_list"><code>advtemplate_list</code></a> configuration option to return a local promise containing a basic Template structure with self-contained examples.</p>
                
                <p>This example allows for the loading of and interacting with the Template user-interface but cannot be used to create, edit, or save Template items.</p>
                
                <p>See the <a class="mceNonEditable" href="https://www.tiny.cloud/docs/tinymce/7/advanced-templates/">Templates</a> documentation for details on how to setup Templates to interact with external data sources.</p>
                
                <h2>Found a bug?</h2>
                
                <p>If you think you have found a bug please create an issue on the <a href="https://github.com/tinymce/tinymce/issues">GitHub repo</a> to report it to the developers.</p>
                
                <h2>Finally…</h2>
                
                <p>Don’t forget to check out our other product <a href="http://plupload.com" target="_blank">Plupload</a>, your ultimate upload solution featuring HTML5 upload support.</p>
                
                <p>Thanks for supporting TinyMCE! We hope it helps you and your users create great content.</p>
                
                <p>All the best from the TinyMCE team.</p>
                      </textarea>
                </div>
            </div>
        </div>
        <!-- [ form-element ] end -->
    </div>
    <!-- [ Main Content ] end -->
@endsection

@section('scripts')
    <!-- [Page Specific JS] start -->
    <script src="{{ URL::asset('build/js/plugins/tinymce/tinymce.min.js') }}"></script>
    <!-- tagify -->
    <script>
        tinymce.init({
            selector: '#pc-tinymce-1',
            toolbar: false,
            height: '400',
            content_style: 'body { font-family: "Inter", sans-serif; }',
            statusbar: false
        });

        tinymce.init({
            selector: '#pc-tinymce-2',
            height: '400',
            content_style: 'body { font-family: "Inter", sans-serif; }'
        });

        tinymce.init({
            height: '400',
            selector: '#pc-tinymce-3',
            content_style: 'body { font-family: "Inter", sans-serif; }',
            toolbar: 'advlist | autolink | link image | lists charmap | print preview',
            plugins: 'advlist autolink link image lists charmap print preview'
        });

        tinymce.init({
            height: '400',
            selector: '#pc-tinymce-4',
            content_style: 'body { font-family: "Inter", sans-serif; }',
            menubar: false,
            toolbar: [
                'styleselect fontselect fontsizeselect',
                'undo redo | cut copy paste | bold italic | link image | alignleft aligncenter alignright alignjustify',
                'bullist numlist | outdent indent | blockquote subscript superscript | advlist | autolink | lists charmap | print preview |  code'
            ],
            plugins: 'advlist autolink link image lists charmap print preview code'
        });
    </script>
    <!-- [Page Specific JS] end -->
@endsection
