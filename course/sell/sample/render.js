// Standalone copy of the in-page Markdown renderer (for the print build + tests)
function esc(s){return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");}
function mdInline(s){
  var codes=[];
  s=s.replace(/`([^`]+)`/g,function(_,c){codes.push(c);return "\u0001"+(codes.length-1)+"\u0001";});
  s=esc(s);
  s=s.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g,function(_,t,u){return '<a href="'+u.replace(/"/g,"&quot;")+'">'+t+"</a>";});
  s=s.replace(/\*\*([^*]+)\*\*/g,"<strong>$1</strong>");
  s=s.replace(/\u0001(\d+)\u0001/g,function(_,i){return "<code>"+esc(codes[+i])+"</code>";});
  return s;
}
function mdList(lines){
  var items=[],cur=null,base=null;
  lines.forEach(function(ln){
    var m=ln.match(/^(\s*)([-*]|\d+\.)\s+(.*)$/);
    if(m&&(base===null||m[1].length<=base)){
      base=base===null?m[1].length:base;
      cur={text:m[3],marker:m[2],kids:[]};items.push(cur);
    }else if(cur){cur.kids.push(ln.replace(new RegExp("^\\s{0,"+((base||0)+2)+"}"),""));}
  });
  var ordered=items[0]&&/\d+\./.test(items[0].marker);
  var html=items.map(function(it){
    var inner=mdInline(it.text);
    if(it.kids.length)inner+=mdToHtml(it.kids.join("\n"));
    return "<li>"+inner+"</li>";
  }).join("");
  return (ordered?"<ol>":"<ul>")+html+(ordered?"</ol>":"</ul>");
}
function mdToHtml(md){
  md=md.replace(/\r\n?/g,"\n");
  var lines=md.split("\n"),out=[],i=0;
  var rawTag=/^<\/?(details|summary|div|table|thead|tbody|tr|td|th|img|br|hr)\b/i;
  while(i<lines.length){
    var line=lines[i];
    var fence=line.match(/^```(.*)$/);
    if(fence){
      var lang=fence[1].trim(),buf=[];i++;
      while(i<lines.length&&!/^```/.test(lines[i])){buf.push(lines[i]);i++;}
      i++;
      out.push("<pre><code"+(lang?' class="language-'+lang+'"':"")+">"+esc(buf.join("\n"))+"</code></pre>");
      continue;
    }
    if(rawTag.test(line.trim())){out.push(line);i++;continue;}
    if(/^---+\s*$/.test(line)){out.push("<hr>");i++;continue;}
    var h=line.match(/^(#{1,6})\s+(.*)$/);
    if(h){var lv=h[1].length;out.push("<h"+lv+">"+mdInline(h[2])+"</h"+lv+">");i++;continue;}
    if(/^>\s?/.test(line)){
      var q=[];
      while(i<lines.length&&/^>\s?/.test(lines[i])){q.push(lines[i].replace(/^>\s?/,""));i++;}
      out.push("<blockquote>"+mdToHtml(q.join("\n"))+"</blockquote>");
      continue;
    }
    if(/\|/.test(line)&&i+1<lines.length&&/-/.test(lines[i+1])&&/^\s*\|?[\s:|-]+\|[\s:|-]*$/.test(lines[i+1])){
      var cells=function(r){return r.trim().replace(/^\|/,"").replace(/\|$/,"").split("|").map(function(c){return c.trim();});};
      var th=cells(line).map(function(c){return "<th>"+mdInline(c)+"</th>";}).join("");
      i+=2;var trs=[];
      while(i<lines.length&&/\|/.test(lines[i])&&lines[i].trim()!==""){
        trs.push("<tr>"+cells(lines[i]).map(function(c){return "<td>"+mdInline(c)+"</td>";}).join("")+"</tr>");i++;
      }
      out.push("<table><thead><tr>"+th+"</tr></thead><tbody>"+trs.join("")+"</tbody></table>");
      continue;
    }
    if(/^(\s*)([-*]|\d+\.)\s+/.test(line)){
      var lb=[];
      while(i<lines.length&&(/^(\s*)([-*]|\d+\.)\s+/.test(lines[i])||(/^\s+\S/.test(lines[i])&&lb.length))){lb.push(lines[i]);i++;}
      out.push(mdList(lb));
      continue;
    }
    if(line.trim()===""){i++;continue;}
    var pb=[line];i++;
    while(i<lines.length&&lines[i].trim()!==""&&!/^(#{1,6}\s|>\s?|```|---+\s*$|(\s*)([-*]|\d+\.)\s+)/.test(lines[i])&&!rawTag.test(lines[i].trim())){pb.push(lines[i]);i++;}
    out.push("<p>"+mdInline(pb.join(" "))+"</p>");
  }
  return out.join("\n");
}
module.exports={mdToHtml:mdToHtml,mdInline:mdInline,esc:esc};

if(require.main===module){
  var fs=require("fs");
  ["00-orientation/00-08-critical-thinking.md","00-orientation/00-09-spec-thinking.md","01-html-css/01-01-html-basics.md"].forEach(function(f){
    var md=fs.readFileSync("/home/user/test/course/"+f,"utf8");
    var h=mdToHtml(md);
    function count(s,re){return (h.match(re)||[]).length;}
    console.log("---",f,"---");
    console.log("  blockquote:",count(h,/<blockquote>/g),"/close",count(h,/<\/blockquote>/g));
    console.log("  table:",count(h,/<table>/g),"th:",count(h,/<th>/g));
    console.log("  pre/code:",count(h,/<pre>/g));
    console.log("  h2:",count(h,/<h2>/g),"ul:",count(h,/<ul>/g),"ol:",count(h,/<ol>/g));
    console.log("  <details> passthrough:",count(h,/<details>/g));
    console.log("  stray unrendered '> ':",count(h,/^&gt; /gm),"| stray '#':",count(h,/<p>#{1,6} /g));
  });
}
