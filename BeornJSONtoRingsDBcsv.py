import json
import csv
import sys
import re
# This script converts a JSON file obtained via the HoB API and converst it into the csv file with the proper column structurefor uploading into RingsDB.

# First you must obtain a JSON file using the HoB API like so: with http://hallofbeorn.com/Export/Search?CardSet=Two-Player%20Limited%20Edition%20Starter
# Then call this function with that JSON file as the argument

# Load JSON file
with open(sys.argv[1]) as f:
        beornJSON = json.load(f)
beornJSON = sorted(beornJSON, key=lambda c: c['Number']) 

# List of traits that need bold/italic formatting when they appear in card text
traitlist = ['Ally','Angmar','Archer','Armor','Armory','Armour','Arnor','Artifact','Attack','Balrog','Barrow','Battleground','Beorning','Besieger','Blackroot Vale','Blight','Boar Clan','Boar','Boon','Bree','Bridge','Brigand','Burglar','Cair Andros','Camp','Captain','Captive','Capture','Captured','Carn Dûm','Cave','Chamber','Champion','Cirith Ungol','City','Clue','Coastland','Condition','Corrupt','Corruption','Corsair','Cosair','Craftsman','Creature','Curse','Dale','Dark','Dead Marshes','Deck','Desert','Despair','Devoured','Disaster','Disguise','Dol Guldur','Doom','Downs','Dragon','Dúnedain','Dungeon','Dunland','Dwarf','Dwelling','Eagle','East Bank','Easterling','Edain','Emyn Muil','Enchantment','Enedwaith','Ent','Erebor','Escape','Esgaroth','Ettenmoors','Favor','Fear','Feat','Fellowship','Flame','Forest','Forge','Fornost','Fortification','Gate','Gaurwaith','Giant','Gift','Goblin','Gollum','Gondor','Gossip','Grey Havens','Grotto','Guardian','Half-elven','Harad','Hazard','Healer','Helm\'s Deep','Hideout','Highlands','Hill','Hills','Hobbit','Hound','House of Bëor','House of Fëanor','House of Finarfin','House of Fingolfin','House of Hador','House of Haleth','Huorn','Information','Initiative','Inn','Insect','Instrument','Isengard','Istari','Item','Ithilien','Key','Lair','Lake-town','Lawn','Legend','Lieutenant','Light','Lórien','Maia','Marhs','Marsh','Marshland','Mathom','Mearas','Minas Tirith','Minstrel','Mirkwood','Mission','Mordor','Morgul','Mount','Mountain','Name','Nameless','Nandor','Nazgûl','Night','Noble','Noise','Noldor','Oath','Oathbreaker','Ocean','Olog-hai','Orc','Osgiliath','Ost-in-Edhil','Outlands','Outpost','Panic','Path','Pelennor','Pier','Pipe','Pipeweed','Plain','Plains','Plan','Plot','Poison','Polluted','Pony','Raider','Ranger','Rat','Raven Clan','Raven','Record','Refuge','Ring','Ring-bearer','River','Riverland','Road','Rohan','Ruins','Sack','Scheme','Scout','Scroll','Search','Shadow','Ship','Shire','Shirriff','Siege','Signal','Silmaril','Silvan','Skill','Snaga','Snow','Song','Sorcerer','Sorcery','Spell','Spider','Spirit','Spy','Staff','Stair','Steward','Stream','Stronghold','Summoner','Suspect','Swamp','Tale','Tantrum','Teleri','Tentacle','Thrall','Throne Room','Thug','Time','Title','Tools','Traitor','Trap','Tree','Troll','Trollshaws','Umbar','Undead','Underground','Underwater','Underworld','Uruk','Uruk-hai','Vala','Vale','Valley','Vampire','Veteran','Village','Villain','Warg','Warrior','Wasteland','Water','Weapon','Weather','Werewolf','West Bank','Western Lands','Wight','Wilderlands','Wolf','Woodman','Wose','Wound','Wraith']

# Words that might follow one of the above traits in a card's text
nounlist = ['hero','character','ally','attachment','card','enemy','location']

# RingsDB card types
playercardtypes = ['Hero','Ally','Attachment','Event','Player Side Quest','Contract','Treasure','Boon']

# Make "Action" and "Response" etc bold.
def boldActions(text):
        text = re.sub(
                r"([A-Z]\w+(?: ?\w*)):",
                r"<b>\1</b>:",
                text)
        return text

# Make traits bold/italic
def boldTraits(text):
        for trait in traitlist:
                for noun in nounlist:
                        text = text.replace(trait+' '+noun,'<b><i>'+trait+'</i></b>'+' '+noun)
        return text

# Format stats (attack/defense etc.) into the correct symbols
def formatRingsdbStats(text):
        text = text.replace("Attack","[attack]")
        text = text.replace("Defense","[defense]")
        text = text.replace("Willpower","[willpower]")
        text = text.replace("Threat","[threat]")
        text = text.replace("Leadership","[leadership]")
        text = text.replace("Spirit","[spirit]")
        text = text.replace("Tactics","[tactics]")
        text = text.replace("Lore","[lore]")
        text = text.replace("Baggins","[baggins]")
        text = text.replace("Fellowship","[fellowship]")
        return text

def formatOctgnStats(text):
        text = text.replace("Attack","Û")
        text = text.replace("Defense","Ú")
        text = text.replace("Willpower","Ò")
        text = text.replace("Threat","$")
        text = text.replace("Leadership","Ì")
        text = text.replace("Spirit","Ê")
        text = text.replace("Tactics","Ï")
        text = text.replace("Lore","Î")
        text = text.replace("Baggins","Í")
        text = text.replace("Fellowship","☺")
        return text

# Format a card's text
def formatRingsdbText(text):
        text = re.sub(r"\.([A-z])",r". \1",text)
        text = text.replace("`","'")
        text = boldActions(text)
        text = boldTraits(text)
        text = formatRingsdbStats(text)
        return text

def formatOctgnText(text):
        text = formatOctgnStats(text)
        return text

# Open output csv file for writing
ringsdbCSV = open('RingsDBset.csv', 'w')
ringsdbwriter = csv.writer(ringsdbCSV)

octgnCSV = open('Octgnset.csv', 'w')
octgnwriter = csv.writer(octgnCSV)

# Write the header line
header = ['pack','type','sphere','position','code','name','traits','text','flavor','isUnique','cost','threat','willpower','attack','defense','health','victory','quest','quantity','deckLimit','illustrator','octgnid','hasErrata']       
ringsdbwriter.writerow(header)


# Loop over the cards in the JSON file
for i,c in enumerate(beornJSON):

        # Get the card type
        cardtype = c['CardType']
        if cardtype == 'Player_Side_Quest': cardtype = 'Player Side Quest'

        # Traits
        traits = ''
        for trait in c['Front']['Traits']:
                traits = traits+trait+' '
        traits = traits.strip() # Remove leading/trailing whitespace

        # Text in ringsdb coposes both Keywords and regular card text
        plaintext = ''
        victory = ''
        keywords = ''
        # Get keywords
        for keyword in c['Front']['Keywords']:
                if 'Victory' in keyword:
                        victory = keyword.replace('Victory ','')
                        victory = victory.replace('.','')
                        continue
                keywords = keywords+keyword+' '
        keywords = keywords.strip() # Remove leading/trailing whitespace
        # Get card text
        for line in c['Front']['Text']:
                plaintext = plaintext+line
        # Format text
        ringsdbtext = formatRingsdbText(plaintext)
        octgntext = formatOctgnText(plaintext)

        # For ringsdb we combine keywords with text
        if len(keywords)>0:
                ringsdbtext = keywords+'\n'+ringsdbtext

        # Flavor text
        flavor = c['Front']['FlavorText']
        if flavor: flavor = flavor.replace("`",'"')

        # Unique
        isunique = ''
        if c['IsUnique']:
                isunique = '1'

        # Sphere
        sphere = c['Sphere']
        if not sphere and cardtype in playercardtypes:
                sphere = 'Neutral'

        # Stats
        cost = ''
        threatcost = ''
        engcost = ''
        threat = ''
        willpower = ''
        attack = ''
        defense = ''
        health = ''
        quest = ''
        shadow = ''
        stagenum=''
        stageletter=''
        if 'ResourceCost' in c['Front']['Stats'].keys(): cost = c['Front']['Stats']['ResourceCost']
        if 'EngagementCost' in c['Front']['Stats'].keys(): engcost = c['Front']['Stats']['EngagementCost']
        if 'ThreatCost' in c['Front']['Stats'].keys(): threatcost = c['Front']['Stats']['ThreatCost']
        if 'Threat' in c['Front']['Stats'].keys(): threat = c['Front']['Stats']['Threat']
        if 'Willpower' in c['Front']['Stats'].keys(): willpower = c['Front']['Stats']['Willpower']
        if 'Attack' in c['Front']['Stats'].keys(): attack = c['Front']['Stats']['Attack']
        if 'Defense' in c['Front']['Stats'].keys(): defense = c['Front']['Stats']['Defense']
        if 'HitPoints' in c['Front']['Stats'].keys(): health = c['Front']['Stats']['HitPoints']
        if 'QuestPoints' in c['Front']['Stats'].keys(): quest = c['Front']['Stats']['QuestPoints']

        # Decklimit
        limit = '3'
        if c['CardType'] == 'Hero': limit = '1'
        elif '1 per deck' in plaintext: limit = '1'

        # Errata
        haserrata = ''
        if c['HasErrata'] == 'true':
                haserrata = '1'
                
        # Construct row to write to ringsdb csv file
        ringsdbrow = [c['CardSet'],c['CardType'],sphere,c['Number'],'',c['Title'],traits,ringsdbtext,flavor,isunique,cost,threatcost,willpower,attack,defense,health,victory,quest,c['Quantity'],limit,c['Artist'],'',haserrata]

        # Additional considerations for Octgn
        uniquesymbol = ''
        if isunique:
                uniquesymbol = '‰'

        backoctgntext = ''
        backquest = ''
        backstagenum=''
        backstageletter=''
        backtype=''
        backtitle=''
        if c['CardType']=='Quest':
                for line in c['Back']['Text']:
                        plaintext = plaintext+line
                backoctgntext = formatOctgnText(plaintext)
                backtitle = c['Title']
                backquest = quest
                backtype = c['CardType']
                quest = ''

        encounterset = ''
        if 'EncounterInfo' in c.keys():
                if c['EncounterInfo'] and 'EncounterSet' in c['EncounterInfo'].keys():
                        encounterset = c['EncounterInfo']['EncounterSet']
        octgnrow = ['','','',c['Number'],c['Quantity'],encounterset,c['Title'],uniquesymbol,c['CardType'],sphere,traits,keywords,threatcost+cost,engcost,threat,willpower,attack,defense,health,quest,victory,octgntext,shadow,'',backtitle,'',backtype,'','','',backstagenum,backstageletter,'','','','','',backquest,'',backoctgntext]

        # Write row
        ringsdbwriter.writerow(ringsdbrow)
        octgnwriter.writerow(octgnrow)
        
# Close file
ringsdbCSV.close()
print("RinsDB csv written")
octgnCSV.close()
print("OCTGN csv written")
