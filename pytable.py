"""Python script???"""


import json
import requests

def saveCache(data,name):
    return nil

def getProfileData(profile):
    if not profile:
        profile = 'Enterprise All'
    slick = 'http://oneview.rackspace.com/slick.php'
    slickoptions = '?fmt=json&latency_20&profile='
    slickurl = ''.join([slick, slickoptions, profile])
    r = requests.get(slickurl)
    return r.json()


def getCachedProfile(profile,age):
    return nil

def getQueueData(profiles):
    return data

def getSummaries(profiles):
    return summary

def printQueue():
    print 'queue'

def findAgedTickets(queue,hours):
    return queue

def findStatus(queue,status):
    return queue

def findMultiTicketAccounts(ticket,minCount):
    return queue

def showFilters():
    return filters

def showProfiles():
    return profiles

def main():
    entf_lin = getProfileData('Enterprise F1000 (Linux)')


if __name__ == '__main__':
    main()
