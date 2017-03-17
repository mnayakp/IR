#!/usr/bin/env python
import networkx as nx
import operator

fileinput = open("/home/meghananyakp/CrawledData/pageRank.csv", "r")
urllist = []
G = nx.DiGraph()
for line in fileinput:
	firstUrl = True
	src_url = ""
	for url in line.split(','):
		if firstUrl:
			firstUrl = False
			src_url = url
			urllist.append(src_url)
		else:
			G.add_edges_from([(src_url.strip(),url.strip())])

pgrank = nx.pagerank(G, alpha=0.85)
fileinput.close

path = {}
fileinput = open("/home/meghananyakp/CrawledData/SolrInput.csv", "r")
for line in fileinput:
	firstUrl = True
	src_url = ""
	for word in line.split(','):
		if firstUrl:
			firstUrl = False
			src_url = word.strip()
		else:
			path[src_url] = word.strip()
fileinput.close

fileoutput = open("/home/meghananyakp/external_pageRankFile.txt","w")
sorted_pgrank = sorted(pgrank.items(), key=operator.itemgetter(1), reverse=True)
for pr in sorted_pgrank:
	if pr[0] != '' and pr[0] in urllist:
		fileoutput.write(path[pr[0]])
		fileoutput.write("=")
		fileoutput.write(str(pgrank[pr[0]]))
		fileoutput.write('\n')
fileoutput.close
